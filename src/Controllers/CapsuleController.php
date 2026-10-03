<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Http\HttpException;
use App\Mailer;
use App\Models\Capsule;
use App\Repositories\CapsuleRepository;
use App\Session;
use App\Storage;
use App\Support\Format;
use App\Validation\CapsuleValidator;
use App\View;

// Pages for creating, viewing, downloading and deleting capsules.
class CapsuleController extends Controller
{
    public function __construct(
        View $view,
        private readonly Auth $auth,
        private readonly Session $session,
        private readonly CapsuleRepository $capsules,
        private readonly CapsuleValidator $validator,
        private readonly Storage $storage,
        private readonly Mailer $mailer,
    ) {
        parent::__construct($view);
    }

    // GET /
    public function index(): void
    {
        $user = $this->auth->requireUser();

        $this->render('index', [
            'title' => 'My capsules',
            'nav' => 'home',
            'capsules' => $this->capsules->forUser($user->id),
        ]);
    }

    // GET /capsules/new
    public function create(): void
    {
        $this->auth->requireUser();

        $this->renderForm([
            'title' => '',
            'message' => '',
            'open_at' => '',
            'recipient_email' => '',
            'is_public' => false,
        ], []);
    }

    // POST /capsules
    public function store(): void
    {
        $user = $this->auth->requireUser();

        [$data, $errors, $upload] = $this->validator->validate($_POST, $_FILES['attachment'] ?? null);
        if ($errors) {
            $this->renderForm($data, $errors, 422);
            return;
        }

        // 1. Save the attachment (if any).
        $data['file_path'] = null;
        $data['file_name'] = null;
        $data['file_mime'] = null;
        if ($upload !== null) {
            $data['file_path'] = $this->storage->save($upload['file'], $upload['extension']);
            $data['file_name'] = mb_substr(basename($upload['file']['name']), 0, 255);
            $data['file_mime'] = $upload['mime'];
        }

        // 2. Insert the row.
        $data['user_id'] = $user->id;
        $capsule = $this->capsules->create($data);

        // 3. "Send" the confirmation e-mail. This is the slow part of the request (see Mailer).
        $recipient = $capsule->recipientEmail ?? $user->email;
        $this->mailer->send($capsule, $recipient, sprintf(
            'Capsule "%s" was sealed for you until %s.',
            $capsule->title,
            Format::dateTime($capsule->openAt)
        ));

        $this->session->flash('success', 'Capsule sealed.');
        $this->redirect('/capsules/' . $capsule->id);
    }

    // GET /capsules/{id}
    public function show(int $id): void
    {
        $user = $this->auth->user();
        $capsule = $this->findVisible($id);
        $isOwner = $capsule->isOwnedBy($user);

        $this->render('show', [
            'title' => $capsule->title,
            'nav' => $isOwner ? 'home' : 'wall',
            'capsule' => $capsule,
            'isOwner' => $isOwner,
        ]);
    }

    // GET /capsules/{id}/file
    // Files are never served directly by the web server: every download goes through
    // this method, so the access rules always apply.
    public function file(int $id): void
    {
        $capsule = $this->findVisible($id);

        if (!$capsule->isOpened()) {
            throw new HttpException(403); // only the owner gets here, and the capsule is still sealed
        }
        if (!$capsule->hasFile()) {
            throw new HttpException(404);
        }

        // Images are shown in the page; PDFs and ?download=1 are downloaded.
        $download = ($_GET['download'] ?? '') === '1' || !$capsule->isImage();

        $this->storage->stream(
            $capsule->filePath,
            $capsule->fileMime,
            $capsule->fileName,
            $download ? 'attachment' : 'inline'
        );
    }

    // POST /capsules/{id}/delete
    public function delete(int $id): void
    {
        $capsule = $this->capsules->find($id);
        if ($capsule === null || !$capsule->isOwnedBy($this->auth->user())) {
            throw new HttpException(404);
        }

        $this->storage->delete($capsule->filePath);
        $this->capsules->delete($capsule->id);

        $this->session->flash('success', 'Capsule deleted.');
        $this->redirect('/');
    }

    private function renderForm(array $old, array $errors, int $status = 200): void
    {
        $this->render('create', [
            'title' => 'New capsule',
            'nav' => 'home',
            'old' => $old,
            'errors' => $errors,
            'maxUploadMb' => $this->validator->maxUploadMb(),
        ], $status);
    }

    // Load a capsule the current visitor may see. Otherwise 404, so that we do not even
    // reveal that the capsule exists.
    private function findVisible(int $id): Capsule
    {
        $capsule = $this->capsules->find($id);
        if ($capsule === null || !$capsule->isVisibleTo($this->auth->user())) {
            throw new HttpException(404);
        }
        return $capsule;
    }
}
