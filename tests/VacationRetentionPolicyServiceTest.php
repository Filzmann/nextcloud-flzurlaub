<?php

declare(strict_types=1);

namespace OCP { interface IAppConfig { public function getValueString(string $appId,string $key,string $default=''):string; public function setValueString(string $appId,string $key,string $value):void; } }
namespace {
    $config=new class implements OCP\IAppConfig { public array $values=[];public function getValueString(string $appId,string $key,string $default=''):string{return $this->values[$appId][$key]??$default;}public function setValueString(string $appId,string $key,string $value):void{$this->values[$appId][$key]=$value;}};
    $service=new OCA\AdUrlaub\Service\VacationRetentionPolicyService($config);
    if($service->policy()['enabled']!==false)throw new RuntimeException('Urlaubs-Retention ist ohne Konfiguration nicht deaktiviert.');
    $saved=$service->save(['enabled'=>true,'reviewAfterDays'=>730,'action'=>'REVIEW']);
    if($saved['reviewAfterDays']!==730||!str_contains($service->retentionCriteria(),'730 Tage'))throw new RuntimeException('Urlaubs-Retention wird nicht gespeichert oder beschrieben.');
    $before=$config->values;
    foreach([['enabled'=>true,'reviewAfterDays'=>-1,'action'=>'REVIEW'],['enabled'=>true,'reviewAfterDays'=>30,'action'=>'DELETE']] as $invalid){try{$service->save($invalid);throw new RuntimeException('Ungültige Urlaubs-Retention akzeptiert.');}catch(InvalidArgumentException){}if($config->values!==$before)throw new RuntimeException('Ungültige Regel verändert AppConfig.');}
    echo "AD Urlaub retention policy test passed\n";
}
