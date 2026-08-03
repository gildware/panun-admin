<?php

namespace Modules\ChattingModule\Services;

class StaffChatMessageParser
{
  private const TOKEN_PATTERN = '/@\[([^\]]*)\]\((staff|customer|provider|booking|service|lead):([A-Za-z0-9\-]{1,36})\)/i';

  public function format(?string $message): string
  {
    if ($message === null || $message === '') {
      return '';
    }

    $offset = 0;
    $result = '';

    while (preg_match(self::TOKEN_PATTERN, $message, $matches, PREG_OFFSET_CAPTURE, $offset)) {
      $start = $matches[0][1];
      $result .= e(substr($message, $offset, $start - $offset));
      $result .= $this->renderToken(
        (string) $matches[1][0],
        (string) $matches[2][0],
        (string) $matches[3][0]
      );
      $offset = $start + strlen($matches[0][0]);
    }

    $result .= e(substr($message, $offset));

    return nl2br($result, false);
  }

  public function plainPreview(?string $message, int $limit = 100): string
  {
    if ($message === null || trim($message) === '') {
      return '';
    }

    $text = preg_replace(self::TOKEN_PATTERN, '$1', $message);

    return \Illuminate\Support\Str::limit(trim(strip_tags($text)), $limit);
  }

  /**
   * @return array<int, string>
   */
  public function extractStaffMentionIds(?string $message): array
  {
    if ($message === null || trim($message) === '') {
      return [];
    }

    $ids = [];
    if (preg_match_all(self::TOKEN_PATTERN, $message, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $match) {
        if (strtolower((string) ($match[2] ?? '')) === 'staff' && ! empty($match[3])) {
          $ids[] = (string) $match[3];
        }
      }
    }

    return array_values(array_unique($ids));
  }

  public function buildToken(string $type, string $id, string $label): string
  {
    $label = trim(preg_replace('/[\[\]]/', '', $label));

    return '@['.$label.']('.$type.':'.$id.')';
  }

  private function renderToken(string $label, string $type, string $id): string
  {
    $url = $this->urlFor($type, $id);
    $safeLabel = e($label);
    $safeType = e($this->typeLabel($type));
    $class = 'staff-chat-entity-link staff-chat-entity-'.e($type);
    $attrs = $type === 'staff'
      ? ' data-entity-type="staff" data-entity-id="'.e($id).'" href="'.e($url).'"'
      : ' href="'.e($url).'" target="_blank" rel="noopener"';

    return '<a class="'.$class.' staff-chat-entity-badge"'.$attrs.'>'
      .'<span class="staff-chat-entity-type">'.$safeType.'</span>'
      .'<span class="staff-chat-entity-sep"> · </span>'
      .'<span class="staff-chat-entity-name">'.$safeLabel.'</span>'
      .'</a>';
  }

  private function typeLabel(string $type): string
  {
    return match ($type) {
      'staff' => translate('Staff'),
      'customer' => translate('customer'),
      'provider' => translate('Provider'),
      'booking' => translate('booking'),
      'service' => translate('Service'),
      'lead' => translate('Lead'),
      default => ucfirst($type),
    };
  }

  private function urlFor(string $type, string $id): string
  {
    return match ($type) {
      'staff' => route('admin.chat.staff', ['open_staff' => $id]),
      'customer' => route('admin.customer.detail', [$id, 'web_page' => 'overview']),
      'provider' => route('admin.provider.details', [$id, 'web_page' => 'overview']),
      'booking' => route('admin.booking.details', [$id, 'web_page' => 'details']),
      'service' => route('admin.service.detail', [$id]),
      'lead' => route('admin.lead.show', [$id]),
      default => '#',
    };
  }
}
