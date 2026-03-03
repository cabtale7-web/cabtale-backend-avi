<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Routing\Controller;

class TestMailController extends Controller
{
    public function sendTest()
    {
        Mail::raw('Resend is working 🚀', function ($mail) {
            $mail->to('vaibhavjoshi0709@email.com')
                 ->subject('Resend Test');
        });
        return 'Test email sent!';
    }
}
