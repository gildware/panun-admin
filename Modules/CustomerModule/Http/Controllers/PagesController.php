<?php

namespace Modules\CustomerModule\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

class PagesController extends Controller
{
    /**
     * Legacy /about-us etc. routes — content lives on business-page/{slug}.
     */
    public function aboutUs(): RedirectResponse
    {
        return $this->redirectToBusinessPage('about-us');
    }

    public function privacyPolicy(): RedirectResponse
    {
        return $this->redirectToBusinessPage('privacy-policy');
    }

    public function termsAndConditions(): RedirectResponse
    {
        return $this->redirectToBusinessPage('terms-and-conditions');
    }

    public function refundPolicy(): RedirectResponse
    {
        return $this->redirectToBusinessPage('refund-policy');
    }

    public function returnPolicy(): RedirectResponse
    {
        return $this->redirectToBusinessPage('return-policy');
    }

    public function cancellationPolicy(): RedirectResponse
    {
        return $this->redirectToBusinessPage('cancellation-policy');
    }

    private function redirectToBusinessPage(string $slug): RedirectResponse
    {
        return redirect()->route('business.page.dynamic', ['slug' => $slug]);
    }
}
