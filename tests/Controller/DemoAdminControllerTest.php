<?php

declare(strict_types=1);

namespace OCP {
    interface IRequest {}
    interface IUser { public function getUID(); }
    interface IUserSession { public function getUser(); }
    interface IGroupManager { public function isAdmin($uid); }
}
namespace OCP\AppFramework {
    class Controller { public function __construct(string $appId, \OCP\IRequest $request) {} }
    final class Http { public const STATUS_BAD_REQUEST = 400; public const STATUS_FORBIDDEN = 403; }
}
namespace OCP\AppFramework\Http {
    final class JSONResponse {
        public function __construct(private array $data = [], private int $status = 200) {}
        public function getData(): array { return $this->data; }
        public function getStatus(): int { return $this->status; }
    }
}
namespace Psr\Log { interface LoggerInterface { public function error(string $message, array $context = []): void; } }
namespace OCA\FlzUrlaub\AppInfo { final class Application { public const APP_ID = 'flzurlaub'; } }
namespace OCA\FlzUrlaub\Service {
    final class VacationDemoPackService {
        public int $installations = 0;
        public function install(): array { $this->installations++; return ['createdVacations' => 2]; }
    }
}
namespace {
    use OCA\FlzUrlaub\Controller\DemoAdminController;
    use OCA\FlzUrlaub\Service\VacationDemoPackService;
    use OCP\AppFramework\Http;

    $session = new class implements OCP\IUserSession {
        public ?OCP\IUser $user = null;
        public function getUser(): ?OCP\IUser { return $this->user; }
    };
    $groups = new class implements OCP\IGroupManager {
        public bool $admin = false;
        public function isAdmin($uid): bool { return $this->admin; }
    };
    $demo = new VacationDemoPackService();
    $logger = new class implements Psr\Log\LoggerInterface {
        public array $errors = [];
        public function error(string $message, array $context = []): void { $this->errors[] = $message; }
    };
    $controller = new DemoAdminController(
        new class implements OCP\IRequest {},
        $session,
        $groups,
        $demo,
        $logger,
    );

    $denied = $controller->install();
    if ($denied->getStatus() !== Http::STATUS_FORBIDDEN || $demo->installations !== 0) {
        throw new RuntimeException('Nicht-Admins dürfen das synthetische Demo-Pack nicht installieren.');
    }

    $session->user = new class implements OCP\IUser { public function getUID(): string { return 'technical-admin'; } };
    $groups->admin = true;
    $allowed = $controller->install();
    if ($allowed->getStatus() !== 200 || $demo->installations !== 1) {
        throw new RuntimeException('Aktuelle native Administration benötigt für das rein synthetische Demo-Pack noch fälschlich eine Fachfreigabe.');
    }

    $groups->admin = false;
    $deniedAfterRoleLoss = $controller->install();
    if ($deniedAfterRoleLoss->getStatus() !== Http::STATUS_FORBIDDEN || $demo->installations !== 1) {
        throw new RuntimeException('Nach Entzug des nativen Adminstatus bleibt die Demo-Installation unerlaubt aktiv.');
    }

    echo "Filzmann Urlaubsplanung technical demo admin tests passed\n";
}
