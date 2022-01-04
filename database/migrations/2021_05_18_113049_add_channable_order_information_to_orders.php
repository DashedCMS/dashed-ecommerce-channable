<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddChannableOrderInformationToOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qcommerce__channable_order_connection', function (Blueprint $table) {
            $table->id();

            $table->string('channable_id')->nullable();
            $table->string('project_id')->nullable();
            $table->string('platform_id')->nullable();
            $table->string('platform_name')->nullable();
            $table->string('channel_id')->nullable();
            $table->string('channel_name')->nullable();
            $table->string('status_paid')->nullable();
            $table->string('status_shipped')->nullable();
            $table->string('tracking_code')->nullable();
            $table->string('tracking_original')->nullable();
            $table->string('transporter')->nullable();
            $table->string('transporter_original')->nullable();
            $table->decimal('commission')->nullable();

            $table->timestamps();
        });

        Schema::table('qcommerce__orders', function (Blueprint $table) {
            $table->unsignedBigInteger('channable_order_connection_id')->nullable();
            $table->foreign('channable_order_connection_id')->references('id')->on('qcommerce__channable_order_connection');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            //
        });
    }
}
