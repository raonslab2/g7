<?php
/** Every observer/restore SQL execution asserts the exact TEST database first. */
class W04TestPDO extends PDO {
 public function assertTest(): void {
  $v=parent::query('SELECT DATABASE() AS db,CURRENT_USER() AS account')->fetch(PDO::FETCH_ASSOC);
  if($v!==['db'=>'req81_travel_lab_test','account'=>'req81_travel@127.0.0.1']) { throw new RuntimeException('TEST SQL boundary rejected.'); }
 }
 public function query(string $query,?int $fetchMode=null,mixed ...$args): PDOStatement|false { $this->assertTest();return $fetchMode===null?parent::query($query):parent::query($query,$fetchMode,...$args); }
 public function exec(string $statement): int|false {$this->assertTest();return parent::exec($statement);}
 public function prepare(string $query,array $options=[]):PDOStatement|false {$this->assertTest();return parent::prepare($query,$options);}
}
class W04TestStatement extends PDOStatement {
 protected function __construct(private WeakReference $bounded) {}
 public function execute(?array $params=null): bool {$this->bounded->get()->assertTest();return parent::execute($params);}
}
function w04BoundPDO(string $dsn,string $user,string $password,array $options=[]): W04TestPDO {
 $p=new W04TestPDO($dsn,$user,$password,$options+[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
 $p->setAttribute(PDO::ATTR_STATEMENT_CLASS,[W04TestStatement::class,[WeakReference::create($p)]]);$p->assertTest();return $p;
}
/** Native SQL stays on native services; callback verifies both native PDO endpoints. */
function w04NativeSqlGuard($app): void {
 if(!$app->bound("db")) {$app->registered(static fn()=>w04NativeSqlGuard($app));return;}
 $install=static function($connection):void {
  $connection->beforeExecuting(static function($sql,$bindings,$c):void {
   foreach([$c->getPdo(),$c->getReadPdo()] as $p) {
    $id=$p->query('SELECT DATABASE() AS db,CURRENT_USER() AS account')->fetch(PDO::FETCH_ASSOC);
    if($id!==['db'=>'req81_travel_lab_test','account'=>'req81_travel@127.0.0.1']) {throw new RuntimeException('Native SQL outside TEST.');}
   }
  });
 };
 $install($app['db']->connection('mysql'));
 $app['events']->listen(Illuminate\Database\Events\ConnectionEstablished::class,static fn($event)=>$install($event->connection));
}
class W04NativeMysqlConnection extends Illuminate\Database\MySqlConnection {
 /** Guard lives inside Laravel's native query error/reconnect handling. Never bypass identity. */
 protected function runQueryCallback($query,$bindings,Closure $callback) {
  return parent::runQueryCallback($query,$bindings,function($q,$b) use($callback) {
   foreach([$this->getPdo(),$this->getReadPdo()] as $p){
    $id=$p->query('SELECT DATABASE() AS db,CURRENT_USER() AS account')->fetch(PDO::FETCH_ASSOC);
    if($id!==['db'=>'req81_travel_lab_test','account'=>'req81_travel@127.0.0.1']){throw new RuntimeException('Fixture SQL outside TEST.');}
   }
   return $callback($q,$b);
  });
 }
}
