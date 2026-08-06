<?php

namespace Modules\ChattingModule\Services;

class StaffChatMessageParser
{
  private const TOKEN_PATTERN = '/@\[([^\]]*)\]\((staff|customer|provider|booking|service|lead):([A-Za-z0-9\-]{1,36})\)/i';

  private const DISPLAY_PATTERN = '/@(Staff|Customer|Provider|Booking|Service|Lead|staff|customer|provider|booking|service|lead):([^\n@]+?)(?=\s@(?:Staff|Customer|Provider|Booking|Service|Lead|staff|customer|provider|booking|service|lead):|$)/s';

  public function format(?string $message): string
  {
    if ($message === null || $message === '') {
      return '';
    }

    $offset = 0;
    $length = strlen($message);
    $result = '';

    while ($offset < $length) {
      $remaining = substr($message, $offset);

      if (preg_match(self::TOKEN_PATTERN, $remaining, $matches, PREG_OFFSET_CAPTURE, 0) && ($matches[0][1] ?? -1) === 0) {
        $result .= $this->renderToken(
          (string) $matches[1][0],
          (string) $matches[2][0],
          (string) $matches[3][0]
        );
        $offset += strlen($matches[0][0]);

        continue;
      }

      if (preg_match(self::DISPLAY_PATTERN, $remaining, $matches, PREG_OFFSET_CAPTURE, 0) && ($matches[0][1] ?? -1) === 0) {
        $result .= $this->renderDisplayTag(
          (string) $matches[1][0],
          (string) $matches[2][0]
        );
        $offset += strlen($matches[0][0]);

        continue;
      }

      $nextAt = strpos($remaining, '@');
      if ($nextAt === false) {
        $result .= e($remaining);
        break;
      }

      if ($nextAt === 0) {
        $result .= e('@');
        $offset += 1;

        continue;
      }

      $result .= e(substr($remaining, 0, $nextAt));
      $offset += $nextAt;
    }

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
    $class = 'staff-chat-entity-link staff-chat-entity-'.e($this->normalizeType($type));
    $attrs = $type === 'staff'
      ? ' data-entity-type="staff" data-entity-id="'.e($id).'" href="'.e($url).'"'
      : ' href="'.e($url).'" target="_blank" rel="noopener"';

    return '<a class="'.$class.' staff-chat-entity-badge"'.$attrs.'>'
      .'<span class="staff-chat-entity-type">'.$safeType.'</span>'
      .'<span class="staff-chat-entity-sep"> · </span>'
      .'<span class="staff-chat-entity-name">'.$safeLabel.'</span>'
      .'</a>';
  }

  private function renderDisplayTag(string $typeLabel, string $label): string
  {
    $type = $this->normalizeType($typeLabel);
    $safeLabel = e(trim($label));
    $safeType = e($this->typeLabel($type));
    $class = 'staff-chat-entity-badge staff-chat-entity-'.e($type);

    return '<span class="'.$class.'">'
      .'<span class="staff-chat-entity-type">'.$safeType.'</span>'
      .'<span class="staff-chat-entity-sep"> · </span>'
      .'<span class="staff-chat-entity-name">'.$safeLabel.'</span>'
      .'</span>';
  }

  private function normalizeType(string $type): string
  {
    return strtolower(trim($type));
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
