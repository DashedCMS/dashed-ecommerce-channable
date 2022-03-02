<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpgradeChannableToV2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('qcommerce__channable_order_connection', 'qcommerce__order_channable');

        Schema::table('qcommerce__order_channable', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('id')->constrained('qcommerce__orders');
        });

        foreach (\Qubiqx\QcommerceEcommerceChannable\Models\ChannableOrder::get() as $channableOrder) {
            $channableOrder->order_id = \Qubiqx\QcommerceEcommerceCore\Models\Order::where('channable_order_connection_id', $channableOrder->id)->first()->id;
            $channableOrder->save();
        }

        Schema::table('qcommerce__orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('channable_order_connection_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('v2', function (Blueprint $table) {
            //
        });
    }
}
