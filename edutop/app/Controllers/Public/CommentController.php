<?php

namespace App\Controllers\Public;

use App\Core\Controller;
use App\Core\Recaptcha;
use App\Core\Request;
use App\Core\Sanitizer;
use App\Core\Validator;
use App\Models\BlogComment;
use App\Models\BlogPost;

class CommentController extends Controller
{
    public function store(Request $request): void
    {
        $slug = (string) $request->param('slug');
        $post = BlogPost::findPublishedBySlug($slug);

        if (!$post || !$post['comments_enabled']) {
            $this->redirect('/blog/' . $slug);
        }

        // Honeypot: a hidden field real visitors never fill in. Bots that
        // auto-fill every input trip this; drop the submission silently.
        if (trim((string) $request->input('website')) !== '') {
            $this->redirect('/blog/' . $slug);
        }

        if (!Recaptcha::verify($request->input('g-recaptcha-response'))) {
            $this->flashError('Please complete the reCAPTCHA verification.');
            $this->redirect('/blog/' . $slug);
        }

        $name = Sanitizer::text((string) $request->input('name'));
        $email = trim((string) $request->input('email'));
        $body = Sanitizer::text((string) $request->input('body'));

        $validator = new Validator(['name' => $name, 'email' => $email, 'body' => $body]);
        $validator->required('name')->required('email')->email('email')->required('body')->maxLength('body', 3000);

        if ($validator->fails()) {
            $this->flashError($validator->firstError());
            $this->redirect('/blog/' . $slug);
        }

        BlogComment::create((int) $post['id'], $name, $email, $body, $request->ip());

        $this->flashSuccess('Thanks! Your comment has been submitted and is awaiting approval.');
        $this->redirect('/blog/' . $slug);
    }
}
