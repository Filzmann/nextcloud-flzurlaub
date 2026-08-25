<?php

declare(strict_types=1);

namespace OCA\AdUrlaub\Controller;

use OCA\AdUrlaub\AppInfo\Application;
use OCA\AdUrlaub\Service\VacationDemoPackService;
use OCA\AdUrlaub\Service\TemporaryAdminAccessChecker;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/** Zweck: Startet den Urlaubs-Demo-Pack ausschließlich mit Admin- und CSRF-Schutz. */
final class DemoAdminController extends Controller {
    public function __construct(IRequest $request, private IUserSession $session, private IGroupManager $groups, private VacationDemoPackService $demoPack, private LoggerInterface $logger, private TemporaryAdminAccessChecker $temporaryAdminAccess) {
        parent::__construct(Application::APP_ID, $request);
    }
    public function install(): JSONResponse {
        if (!$this->isAdmin()) return new JSONResponse(['error' => 'Keine Berechtigung.'], Http::STATUS_FORBIDDEN);
        try {
            return new JSONResponse(['result' => $this->demoPack->install()]);
        } catch (\Throwable $error) {
            $this->logger->error('Urlaubs-Demo-Pack konnte nicht installiert werden.', ['exception' => $error]);
            return new JSONResponse(['error' => $error->getMessage()], Http::STATUS_BAD_REQUEST);
        }
    }
    private function isAdmin(): bool {
        $user = $this->session->getUser();
        return $user !== null && $this->groups->isAdmin($user->getUID()) && $this->temporaryAdminAccess->hasActiveGrant($user->getUID());
    }
}
