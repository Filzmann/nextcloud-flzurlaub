<?php

declare(strict_types=1);

namespace OCP\EventDispatcher {
    if (!class_exists(Event::class)) {
        class Event {}
        interface IEventListener { public function handle(Event $event): void; }
    }
}
namespace OCP\Navigation\Events {
    if (!class_exists(LoadAdditionalEntriesEvent::class)) {
        class LoadAdditionalEntriesEvent extends \OCP\EventDispatcher\Event {}
    }
}
namespace OCP {
    if (!interface_exists(IL10N::class)) {
        interface IL10N {
            public function t(string $text, $parameters = []): string;
            public function n(string $text_singular, string $text_plural, int $count, array $parameters = []): string;
            public function l(string $type, $data, array $options = []);
            public function getLanguageCode(): string;
            public function getLocaleCode(): string;
        }
    }
    if (!interface_exists(IURLGenerator::class)) {
        interface IURLGenerator { public function linkToRoute(string $routeName, array $arguments = []): string; public function imagePath(string $appName, string $file): string; }
    }
    if (!interface_exists(IUser::class)) {
        interface IUser { public function getUID(): string; }
    }
    if (!interface_exists(IUserSession::class)) {
        interface IUserSession { public function getUser(): ?IUser; }
    }
}
namespace OCP\Navigation {
    if (!interface_exists(INavigationManager::class)) {
        interface INavigationManager { public const TYPE_APPS = 'link'; public function add(callable $entry): void; }
    }
}
namespace OCP\App {
    if (!interface_exists(IAppManager::class)) {
        interface IAppManager { public function isEnabledForUser($appId, $user = null); }
    }
}

namespace {
    use OCA\FlzUrlaub\Listener\StandaloneNavigationListener;
    use OCP\App\IAppManager;
    use OCP\IL10N;
    use OCP\IURLGenerator;
    use OCP\IUser;
    use OCP\IUserSession;
    use OCP\Navigation\Events\LoadAdditionalEntriesEvent;
    use OCP\Navigation\INavigationManager;

    $l10n = new class implements IL10N {
        public function t(string $text, $parameters = []): string { return $text; }
        public function n(string $text_singular, string $text_plural, int $count, array $parameters = []): string { return $count === 1 ? $text_singular : $text_plural; }
        public function l(string $type, $data, array $options = []) { return (string)$data; }
        public function getLanguageCode(): string { return 'de'; }
        public function getLocaleCode(): string { return 'de_DE'; }
    };
    $user = new class implements IUser { public function getUID(): string { return 'navigation-user'; } };
    $session = new class($user) implements IUserSession { public function __construct(private ?IUser $user) {} public function getUser(): ?IUser { return $this->user; } };
    $apps = new class implements IAppManager { public bool $suiteEnabled = false; public function isEnabledForUser($appId, $user = null): bool { return $appId === 'orgsuite' && $this->suiteEnabled; } };
    $nav = new class implements INavigationManager { public array $entries = []; public function add(callable $entry): void { $this->entries[] = $entry; } };
    $url = new class implements IURLGenerator {
        public function linkToRoute(string $routeName, array $arguments = []): string { return $routeName; }
        public function imagePath(string $appName, string $file): string { return "$appName/$file"; }
    };

    $listener = new StandaloneNavigationListener($session, $apps, $nav, $url, $l10n);
    $listener->handle(new LoadAdditionalEntriesEvent());
    $entry = ($nav->entries[0] ?? static fn(): array => [])();
    if (($entry['id'] ?? '') !== 'flzurlaub' || ($entry['name'] ?? '') !== 'Urlaub' || ($entry['href'] ?? '') !== 'flzurlaub.page.index') {
        throw new RuntimeException('Standalone-Urlaubsnavigation fehlt.');
    }

    $apps->suiteEnabled = true;
    $listener->handle(new LoadAdditionalEntriesEvent());
    if (count($nav->entries) !== 1) throw new RuntimeException('Aktive OrgSuite unterdrückt den Urlaubs-Standalone-Einstieg nicht.');

    echo "Filzmann Urlaubsplanung standalone navigation test passed\n";
}
