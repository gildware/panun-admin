<?php

/**
 * Mobile app AI support knowledge (merged with whatsapp_ai_support in search).
 */
return [

    'general_tips' => [
        'Book a service: tap **Book a service** or tell me what you need — I will guide you step by step like the app booking screen.',
        'Check bookings: open the **Booking** tab in the app, or ask me **booking status** here.',
        'Cart & payment: use the **Cart** icon on Home, review items, then checkout. Pull to refresh if something looks outdated.',
        'Sign-in issues: use the same phone number you booked with; request a new OTP if the code expired.',
        'Address problems: update location from the bar on Home; make sure you are inside our service area.',
        'For urgent safety issues (gas leak, sparks, major flooding), stay safe and call emergency services first.',
    ],

    'faqs' => [
        [
            'q' => 'How do I book a service in the app?',
            'a' => 'Tell me what you need (e.g. AC repair), pick the matching service, choose type, visit time, address, and provider — then add to cart and pay from the Cart screen.',
        ],
        [
            'q' => 'How do I check my booking status?',
            'a' => 'Ask me for booking status here, or open the Booking tab. Have your booking reference (e.g. PK…) ready if you want details on one job.',
        ],
        [
            'q' => 'Payment failed or cart issue',
            'a' => 'Open Cart from Home, confirm address and schedule, then try checkout again. If payment failed, check your UPI/card and retry; your cart is saved.',
        ],
        [
            'q' => 'Provider did not arrive',
            'a' => 'Open the booking in the Booking tab for updates. If the visit is late, use Help & Support from the menu with your booking reference.',
        ],
        [
            'q' => 'Cancel or reschedule',
            'a' => 'Open the booking in the Booking tab — cancel or change options appear when allowed by status. I can explain your current status if you share the booking reference.',
        ],
    ],

    'troubleshooting' => [
        'otp' => [
            'title' => 'OTP / sign-in',
            'steps' => [
                'Confirm the phone number is correct and has network signal.',
                'Tap resend OTP and wait a full minute before trying again.',
                'Disable airplane mode; avoid switching SIM during verification.',
                'If it still fails, sign out, force-close the app, reopen and try once more.',
            ],
        ],
        'payment' => [
            'title' => 'Payment / checkout',
            'steps' => [
                'Open Cart from Home and confirm every line item and address.',
                'Check UPI/card balance and bank app notifications.',
                'If payment shows failed but money was deducted, wait 10 minutes and check Booking tab before paying again.',
                'Contact support with a screenshot if the amount was debited twice.',
            ],
        ],
        'cart' => [
            'title' => 'Cart',
            'steps' => [
                'Pull to refresh on Home, then open Cart again.',
                'Ensure your saved address is in a service zone (location bar on Home).',
                'Remove outdated items and add the service again from Home or ask me to book.',
            ],
        ],
        'address' => [
            'title' => 'Address / location',
            'steps' => [
                'From Home, tap the location bar and pick or add an address.',
                'Use **Set from map** if search does not find your lane.',
                'Save the address, then retry booking or checkout.',
            ],
        ],
        'booking' => [
            'title' => 'Booking tab / status',
            'steps' => [
                'Open the Booking tab and pull to refresh.',
                'Confirm you are signed in with the same phone used when booking.',
                'Tap a booking for timeline and provider details.',
                'Ask me here with your booking reference (PK…) for a quick status summary.',
            ],
        ],
        'ac' => [
            'title' => 'AC / cooling',
            'steps' => [
                'Use **Cool** mode and set temperature below room temperature; wait 5–10 minutes.',
                'Check and clean the indoor filter; clear debris around the outdoor unit.',
                'Water dripping inside — turn off AC; drain line may be blocked.',
                'Burning smell or sparks — switch off at mains and book a technician.',
            ],
        ],
        'plumb' => [
            'title' => 'Plumbing',
            'steps' => [
                'Shut the main water valve if there is an active leak.',
                'For slow drains, try hot water flush; avoid mixing harsh chemicals.',
                'Geyser not heating — check power and thermostat before booking.',
            ],
        ],
        'electric' => [
            'title' => 'Electrical',
            'steps' => [
                'Reset the tripped MCB/breaker once; if it trips again, unplug loads on that circuit.',
                'Do not touch exposed wires; sparks or burning smell means switch off mains.',
            ],
        ],
        'notification' => [
            'title' => 'Notifications',
            'steps' => [
                'Allow notifications for this app in phone Settings.',
                'Stay signed in; open the bell icon on Home to read alerts.',
            ],
        ],
    ],

];
