<?php

namespace Modules\Raonslab\TravelLab\Tests\Fixtures;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar;
use Illuminate\Support\Fluent;

/**
 * MySQL의 테이블별 index 이름을 SQLite의 DB 전체 namespace로 변환한다.
 * 키·고유성·외래키 자체는 실제 마이그레이션대로 유지한다.
 */
class WorkflowSqliteGrammar extends SQLiteGrammar
{
    private function qualified(Blueprint $blueprint, Fluent $command): Fluent
    {
        $command = clone $command;
        if (! str_starts_with($command->index, $blueprint->getTable().'_')) {
            $command->index = $blueprint->getTable().'_'.$command->index;
        }

        return $command;
    }

    public function compileIndex(Blueprint $blueprint, Fluent $command)
    {
        return parent::compileIndex($blueprint, $this->qualified($blueprint, $command));
    }

    public function compileUnique(Blueprint $blueprint, Fluent $command)
    {
        return parent::compileUnique($blueprint, $this->qualified($blueprint, $command));
    }

    public function compileDropIndex(Blueprint $blueprint, Fluent $command)
    {
        return parent::compileDropIndex($blueprint, $this->qualified($blueprint, $command));
    }

    public function compileDropUnique(Blueprint $blueprint, Fluent $command)
    {
        return parent::compileDropUnique($blueprint, $this->qualified($blueprint, $command));
    }
}
