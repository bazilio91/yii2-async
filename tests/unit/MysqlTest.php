<?php
namespace bazilio\async\tests\unit;

class MysqlTest extends BaseTestClass
{
    public $appConfig = '@tests/unit/_config.mysql.php';

    protected static $migrated = false;

    public static function migrate()
    {
        if (self::$migrated) {
            return;
        }

        $migrateController = new \yii\console\controllers\MigrateController('migrate', \Yii::$app);
        $migrateController->migrationPath = '@tests/../migrations';
        $migrateController->runAction('up', ['interactive' => 0]);

        self::$migrated = true;
    }

    public function loadFixtures($fixtures = null)
    {
        $this->migrate();
        parent::loadFixtures($fixtures);
    }
}
