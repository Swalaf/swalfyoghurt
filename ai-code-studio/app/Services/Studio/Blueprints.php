<?php

namespace App\Services\Studio;

/**
 * Deterministic specs/stacks used when no AI provider is connected (and as
 * the shape the AI is asked to fill in).
 */
class Blueprints
{
    public const KINDS = [
        ['◰', 'Website', 'Pages people can visit — business sites, portfolios, blogs.'],
        ['▣', 'Web app', 'Tools people log in to — dashboards, portals, SaaS.'],
        ['▯', 'Phone app', 'For iPhone and Android.'],
        ['⊡', 'Online shop', 'Sell products with payments and orders.'],
        ['◷', 'Booking system', 'Appointments, reservations and reminders.'],
        ['⚙', 'Automation', 'Let the computer do repetitive work for you.'],
        ['✦', 'AI tool', 'Chatbots, writing helpers, smart search.'],
        ['＋', 'Something else', 'Describe it and I’ll figure it out.'],
    ];

    public const STACK_OPTIONS = [
        'frontend' => ['Next.js', 'React', 'Vue', 'HTML/CSS'],
        'backend' => ['Laravel', 'Node.js', 'Python', 'PHP'],
        'mobile' => ['Flutter', 'React Native', 'None'],
        'database' => ['PostgreSQL', 'MySQL', 'SQLite', 'MongoDB'],
        'deployment' => ['Docker · VPS', 'cPanel', 'Vercel', 'Custom'],
    ];

    public static function spec(string $kind, string $idea): array
    {
        $base = match ($kind) {
            'Website' => [
                'features' => ['Home page with hero', 'About section', 'Services / menu', 'Contact form', 'Mobile friendly layout'],
                'roles' => ['Visitor', 'Site owner'],
                'pages' => ['/', '/about', '/services', '/contact'],
                'database' => ['messages'],
                'api' => ['POST /contact'],
            ],
            'Online shop' => [
                'features' => ['Product catalogue', 'Cart & checkout', 'Order history', 'Admin product manager', 'Payments'],
                'roles' => ['Admin', 'Customer'],
                'pages' => ['/', '/products/[id]', '/cart', '/checkout', '/admin/products'],
                'database' => ['users', 'products', 'orders', 'order_items', 'payments'],
                'api' => ['GET /products', 'POST /cart', 'POST /orders', 'POST /payments/webhook'],
            ],
            'Booking system' => [
                'features' => ['Service list', 'Calendar availability', 'Online booking', 'Email / SMS reminders', 'Admin schedule'],
                'roles' => ['Admin', 'Staff', 'Customer'],
                'pages' => ['/', '/book', '/my-bookings', '/admin/calendar'],
                'database' => ['users', 'services', 'staff', 'bookings', 'reminders'],
                'api' => ['GET /availability', 'POST /bookings', 'DELETE /bookings/:id'],
            ],
            'Phone app' => [
                'features' => ['Sign up & log in', 'Home feed', 'Profile', 'Push notifications', 'Offline support'],
                'roles' => ['User', 'Admin'],
                'pages' => ['Home', 'Profile', 'Settings', 'Notifications'],
                'database' => ['users', 'devices', 'notifications'],
                'api' => ['POST /auth/login', 'GET /feed', 'POST /devices'],
            ],
            'AI tool' => [
                'features' => ['Prompt input', 'Streaming answers', 'History', 'Usage limits', 'Admin settings'],
                'roles' => ['User', 'Admin'],
                'pages' => ['/', '/history', '/settings'],
                'database' => ['users', 'conversations', 'messages', 'usage'],
                'api' => ['POST /ask', 'GET /history'],
            ],
            'Automation' => [
                'features' => ['Triggers', 'Scheduled runs', 'Run history', 'Notifications'],
                'roles' => ['Owner'],
                'pages' => ['/', '/runs', '/settings'],
                'database' => ['jobs', 'runs', 'logs'],
                'api' => ['POST /run', 'GET /runs'],
            ],
            default => [
                'features' => ['Sign up & log in', 'Dashboard', 'Records management', 'Search & filters', 'Admin analytics'],
                'roles' => ['Admin', 'Member'],
                'pages' => ['/dashboard', '/records', '/records/[id]', '/settings', '/admin'],
                'database' => ['users', 'records', 'activity'],
                'api' => ['GET /records', 'POST /records', 'PATCH /records/:id'],
            ],
        };

        return $base + [
            'phases' => ['Auth & roles', 'Data model & migrations', 'User interface', 'Tests', 'Publish'],
            'summary' => $idea,
        ];
    }

    public static function stack(string $kind): array
    {
        return [
            'frontend' => $kind === 'Website' ? 'HTML/CSS' : 'Vue',
            'backend' => in_array($kind, ['Website'], true) ? 'PHP' : 'Laravel',
            'mobile' => $kind === 'Phone app' ? 'Flutter' : 'None',
            'database' => 'MySQL',
            'deployment' => 'cPanel',
            'why' => 'A static front end previews instantly and publishes anywhere; Laravel + MySQL runs on any cPanel host when you need a backend.',
        ];
    }

    /**
     * Plain-language plan items for the Simple "Here’s what I’ll build" step.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    public static function planItems(array $spec): array
    {
        return collect($spec['features'] ?? [])->take(8)->map(fn ($f) => [$f, 'Included in the first version.'])->all();
    }
}
