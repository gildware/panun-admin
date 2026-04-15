<?php

namespace Modules\WhatsAppModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Modules\WhatsAppModule\Entities\WhatsAppChatStatus;
use Modules\WhatsAppModule\Entities\WhatsAppChatTag;
use Modules\WhatsAppModule\Support\SocialInboxChannel;
use Modules\WhatsAppModule\Support\WhatsAppActiveChatsListCache;

class WhatsAppChatConfigController extends Controller
{
    use AuthorizesRequests;

    public function storeStatus(Request $request): RedirectResponse
    {
        $this->authorize('whatsapp_message_template_update');

        if (!Schema::hasTable('whatsapp_chat_statuses')) {
            abort(404);
        }

        $data = $request->validate([
            'status_name' => 'required|string|max:191',
            'status_bucket' => 'required|string|in:open,closed',
            'status_sort_order' => 'nullable|integer|min:0|max:999999',
        ]);

        WhatsAppChatStatus::query()->create([
            'name' => $data['status_name'],
            'bucket' => $data['status_bucket'],
            'sort_order' => (int) ($data['status_sort_order'] ?? 0),
        ]);

        WhatsAppActiveChatsListCache::forgetAll();
        Toastr::success(translate('successfully_updated'));

        return redirect()->route('admin.whatsapp.conversations.index', ['channel' => SocialInboxChannel::current(), 'tab' => 'chat_config']);
    }

    public function updateStatus(Request $request, WhatsAppChatStatus $status): RedirectResponse
    {
        $this->authorize('whatsapp_message_template_update');

        $data = $request->validate([
            'status_name' => 'required|string|max:191',
            'status_bucket' => 'required|string|in:open,closed',
            'status_sort_order' => 'nullable|integer|min:0|max:999999',
        ]);

        $status->update([
            'name' => $data['status_name'],
            'bucket' => $data['status_bucket'],
            'sort_order' => (int) ($data['status_sort_order'] ?? 0),
        ]);

        WhatsAppActiveChatsListCache::forgetAll();
        Toastr::success(translate('successfully_updated'));

        return redirect()->route('admin.whatsapp.conversations.index', ['channel' => SocialInboxChannel::current(), 'tab' => 'chat_config']);
    }

    public function destroyStatus(WhatsAppChatStatus $status): RedirectResponse
    {
        $this->authorize('whatsapp_message_template_update');

        if ($status->threadMetas()->exists()) {
            Toastr::error(translate('whatsapp_chat_status_in_use'));

            return redirect()->route('admin.whatsapp.conversations.index', ['channel' => SocialInboxChannel::current(), 'tab' => 'chat_config']);
        }

        $status->delete();
        WhatsAppActiveChatsListCache::forgetAll();
        Toastr::success(translate('successfully_updated'));

        return redirect()->route('admin.whatsapp.conversations.index', ['channel' => SocialInboxChannel::current(), 'tab' => 'chat_config']);
    }

    public function storeTag(Request $request): RedirectResponse
    {
        $this->authorize('whatsapp_message_template_update');

        if (!Schema::hasTable('whatsapp_chat_tags')) {
            abort(404);
        }

        $data = $request->validate([
            'tag_name' => 'required|string|max:191',
            'tag_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'tag_sort_order' => 'nullable|integer|min:0|max:999999',
        ]);

        WhatsAppChatTag::query()->create([
            'name' => $data['tag_name'],
            'color' => strtolower($data['tag_color']),
            'sort_order' => (int) ($data['tag_sort_order'] ?? 0),
        ]);

        WhatsAppActiveChatsListCache::forgetAll();
        Toastr::success(translate('successfully_updated'));

        return redirect()->route('admin.whatsapp.conversations.index', ['channel' => SocialInboxChannel::current(), 'tab' => 'chat_config']);
    }

    public function updateTag(Request $request, WhatsAppChatTag $tag): RedirectResponse
    {
        $this->authorize('whatsapp_message_template_update');

        $data = $request->validate([
            'tag_name' => 'required|string|max:191',
            'tag_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'tag_sort_order' => 'nullable|integer|min:0|max:999999',
        ]);

        $tag->update([
            'name' => $data['tag_name'],
            'color' => strtolower($data['tag_color']),
            'sort_order' => (int) ($data['tag_sort_order'] ?? 0),
        ]);

        WhatsAppActiveChatsListCache::forgetAll();
        Toastr::success(translate('successfully_updated'));

        return redirect()->route('admin.whatsapp.conversations.index', ['channel' => SocialInboxChannel::current(), 'tab' => 'chat_config']);
    }

    public function destroyTag(WhatsAppChatTag $tag): RedirectResponse
    {
        $this->authorize('whatsapp_message_template_update');

        $tag->delete();
        WhatsAppActiveChatsListCache::forgetAll();
        Toastr::success(translate('successfully_updated'));

        return redirect()->route('admin.whatsapp.conversations.index', ['channel' => SocialInboxChannel::current(), 'tab' => 'chat_config']);
    }
}
