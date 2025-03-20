<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Zahzah\LaravelSupport\Concerns\NowYouSeeMe;
use Zahzah\MicroTenant\Models\Activity\CentralActivity;

return new class extends Migration
{
    use NowYouSeeMe;

    private $__table;

    public function __construct(){
        $this->__table = app(config('database.models.CentralActivity', CentralActivity::class));
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $table_name = $this->__table->getTableName();
        if (!$this->isTableExists()){
            Schema::create($table_name, function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->unsignedTinyInteger('activity_flag');
                $table->string('reference_type', 50);
                $table->string('reference_id', 36);
                $table->unsignedBigInteger('activity_status')->nullable();
                $table->unsignedTinyInteger('status')->default(1);
                $table->text('message')->nullable();
                $table->timestamps();

                $table->index(['reference_type','reference_id'],'activity_sumber');
                $table->index(['activity_flag','reference_type','reference_id'],'activity_sumber_flag');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->__table->getTableName());
    }
};
