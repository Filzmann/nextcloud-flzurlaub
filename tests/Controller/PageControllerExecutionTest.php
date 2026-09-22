<?php

declare(strict_types=1);

namespace OCP { interface IRequest {} }
namespace OCP\AppFramework { class Controller { public function __construct(string $appName,\OCP\IRequest $request){} } }
namespace OCP\AppFramework\Http { final class TemplateResponse { public function __construct(public string $app,public string $template,public array $params=[]){} } }
namespace OCP\AppFramework\Http\Attribute { #[\Attribute] final class NoAdminRequired {} #[\Attribute] final class NoCSRFRequired {} }
namespace OCA\AdUrlaub\AppInfo { final class Application { public const APP_ID='adurlaub'; } }
namespace OCA\AdUrlaub\Service { final class TemporaryAdminAccessService { public function __construct(public bool $canManage,public bool $needsGrant){}public function canManageGrants():bool{return $this->canManage;}public function currentAdminNeedsGrant():bool{return $this->needsGrant;} } }

namespace {
    use OCA\AdUrlaub\Controller\PageController;
    use OCA\AdUrlaub\Service\TemporaryAdminAccessService;
    use OCP\IRequest;

    $response=(new PageController(new class implements IRequest{},new TemporaryAdminAccessService(true,true)))->index();
    if($response->app!=='adurlaub'||$response->template!=='index'||$response->params!==['canManageAdminAccess'=>true,'showMissingAdminGrant'=>true,'showAdminAccessLink'=>true])throw new RuntimeException('Hauptoberfläche erhält nicht die sichere DPO-Rollenmatrix.');
    $nativeAdminOnly=(new PageController(new class implements IRequest{},new TemporaryAdminAccessService(false,true)))->index();
    if(($nativeAdminOnly->params['showAdminAccessLink']??true)!==false)throw new RuntimeException('Nativer Admin ohne Datenschutzrolle erhält einen Freigabelink.');
    echo "AD Urlaub page controller execution test passed\n";
}
