<?php

namespace Modules\WhatsAppModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Modules\WhatsAppModule\Support\SocialInboxChannel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Modules\WhatsAppModule\Entities\WhatsAppConversationTemplate;

class WhatsAppConversationTemplateController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('whatsapp_message_template_update');

        $data = $request->validate([
            'ct_title' => 'required|string|max:191',
            'ct_body' => 'required|string|max:4096',
            'ct_sort_order' => 'nullable|integer|min:0|max:999999',
            'ct_is_active' => 'nullable|boolean',
        ]);

        $row = [
            'title' => $data['ct_title'],
            'body' => $data['ct_body'],
            'sort_order' => (int) ($data['ct_sort_order'] ?? 0),
        ];
        if (Schema::hasColumn('whatsapp_conversation_templates', 'is_active')) {
            $row['is_active'] = $request->boolean('ct_is_active', true);
        }
        WhatsAppConversationTemplate::query()->create($row);

        Toastr::success(translate('successfully_updated'));

        return redirect()->route('admin.whatsapp.conversations.index', ['channel' => SocialInboxChannel::current(), 'tab' => 'quick_replies']);
    }

    public function update(Request $request, WhatsAppConversationTemplate $template): RedirectResponse
    {
        $this->authorize('whatsapp_message_template_update');

        $data = $request->validate([
            'ct_title' => 'required|string|max:191',
            'ct_body' => 'required|string|max:4096',
            'ct_sort_order' => 'nullable|integer|min:0|max:999999',
            'ct_is_active' => 'nullable|boolean',
        ]);

        $row = [
            'title' => $data['ct_title'],
            'body' => $data['ct_body'],
            'sort_order' => (int) ($data['ct_sort_order'] ?? 0),
        ];
        if (Schema::hasColumn('whatsapp_conversation_templates', 'is_active')) {
            $row['is_active'] = $request->boolean('ct_is_active');
        }
        $template->update($row);

        Toastr::success(translate('successfully_updated'));

        return redirect()->route('admin.whatsapp.conversations.index', ['channel' => SocialInboxChannel::current(), 'tab' => 'quick_replies']);
    }

    public function destroy(WhatsAppConversationTemplate $template): RedirectResponse
    {
        $this->authorize('whatsapp_message_template_update');

        $template->delete();

        Toastr::success(translate('successfully_updated'));

        return redirect()->route('admin.whatsapp.conversations.index', ['channel' => SocialInboxChannel::current(), 'tab' => 'quick_replies']);
    }

    public function toggleActive(WhatsAppConversationTemplate $template): RedirectResponse
    {
        $this->authorize('whatsapp_message_template_update');

        if (! Schema::hasColumn('whatsapp_conversation_templates', 'is_active')) {
            Toastr::warning(translate('WhatsApp_conversation_templates_is_active_migration'));

            return redirect()->route('admin.whatsapp.conversations.index', ['channel' => SocialInboxChannel::current(), 'tab' => 'quick_replies']);
        }

        $template->update(['is_active' => ! (bool) $template->is_active]);

        Toastr::success(translate('successfully_updated'));

        return redirect()->route('admin.whatsapp.conversations.index', ['channel' => SocialInboxChannel::current(), 'tab' => 'quick_replies']);
    }
}
