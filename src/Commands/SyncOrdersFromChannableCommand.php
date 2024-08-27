<?php

namespace Dashed\DashedEcommerceChannable\Commands;

use Illuminate\Console\Command;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedEcommerceChannable\Classes\Channable;
use Dashed\DashedEcommerceChannable\Models\ChannableOrder;

class SyncOrdersFromChannableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'channable:sync-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync orders with Channable';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (Channable::isConnected() && Customsetting::get('channable_order_sync_enabled', null, 0)) {
            $orderDatas = Channable::getOrders();
            foreach ($orderDatas as $orderData) {
                $channableOrder = ChannableOrder::where('channable_id', $orderData['id'])->first();
                if ($channableOrder && ! $channableOrder->order) {
                    $channableOrder->delete();
                    $channableOrder = null;
                }

                if (! $channableOrder) {
                    $this->info('Handling order ' . $orderData['id']);
                    Channable::saveNewOrder($orderData);
                }
            }
        }
    }
}
