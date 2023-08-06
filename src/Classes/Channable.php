<?php

namespace Dashed\DashedEcommerceChannable\Classes;

use Exception;
use Illuminate\Support\Facades\Http;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedEcommerceChannable\Models\ChannableOrder;
use Dashed\DashedEcommerceCore\Models\Order;
use Dashed\DashedEcommerceCore\Models\OrderLog;
use Dashed\DashedEcommerceCore\Models\OrderPayment;
use Dashed\DashedEcommerceCore\Models\OrderProduct;
use Dashed\DashedEcommerceCore\Models\Product;

class Channable
{
    public const APIURL = 'https://api.channable.com/v1';

    public static function isConnected($siteId = null)
    {
        if (! $siteId) {
            $siteId = Sites::getActive();
        }

        try {
            $channableApiKey = Customsetting::get('channable_api_key', $siteId);
            $channableCompanyId = Customsetting::get('channable_company_id', $siteId);
            $channableProjectId = Customsetting::get('channable_project_id', $siteId);
            if ($channableApiKey && $channableCompanyId && $channableProjectId) {
                $response = Http::withToken($channableApiKey)->get(self::APIURL . '/companies/' . $channableCompanyId . '/projects/' . $channableProjectId . '/orders');
                $response = json_decode($response->body(), true);
                if (isset($response['orders'])) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            return true;
        }

        return false;
    }

    public static function getOrders($siteId = null)
    {
        if (! $siteId) {
            $siteId = Sites::getActive();
        }

        $channableApiKey = Customsetting::get('channable_api_key', $siteId);
        $channableCompanyId = Customsetting::get('channable_company_id', $siteId);
        $channableProjectId = Customsetting::get('channable_project_id', $siteId);
        if ($channableApiKey && $channableCompanyId && $channableProjectId) {
            $channableOrders = [];
            $channableOrdersResultCount = 100;
            $channableOffset = 0;
            while ($channableOrdersResultCount == 100) {
                $response = Http::withToken($channableApiKey)
                    ->retry(3)
                    ->get(self::APIURL . '/companies/' . $channableCompanyId . '/projects/' . $channableProjectId . '/orders?limit=100&offset=' . $channableOffset)
                    ->json();
                if (isset($response['orders'])) {
                    $channableOrders = array_merge($channableOrders, $response['orders']);
                    $channableOrdersResultCount = count($response['orders']);
                    $channableOffset += 100;
                }
            }

            return $channableOrders;
        }

        return [];
    }

    //    public static function getAllOrders($siteId = null)
    //    {
    //        if (! $siteId) {
    //            $siteId = Sites::getActive();
    //        }
    //
    //        $orderDatas = self::getOrders();
    //        foreach ($orderDatas as $orderData) {
    //            $channableOrder = ChannableOrder::where('channable_id', $orderData['id'])->first();
    //            if ($channableOrder && ! $channableOrder->order) {
    //                $channableOrder->delete();
    //                $channableOrder = null;
    //            }
    //
    //            if (! $channableOrder) {
    //                self::saveNewOrder($orderData, $siteId);
    //            }
    //        }
    //
    ////        $channableOrders = Order::whereNotNull('channable_order_connection_id')->get();
    ////        foreach ($channableOrders as $channableOrder) {
    ////            $orderStillExistsInChannable = false;
    ////
    ////            foreach ($orderDatas as $orderData) {
    ////                if ($channableOrder->channableOrderConnection->channel_id == $orderData['channel_id']) {
    ////                    $orderStillExistsInChannable = true;
    ////                }
    ////                if ($orderData['channel_id'] == 1230442136) {
    ////                    dd($orderData);
    ////                }
    ////            }
    ////
    ////            if (!$orderStillExistsInChannable) {
    ////                dd('kut', $channableOrder);
    ////            }
    ////        }
    //    }

    public static function saveNewOrder($orderData, $siteId = null)
    {
        if (! $siteId) {
            $siteId = Sites::getActive();
        }

        $order = new Order();
        $order->first_name = $orderData['data']['shipping']['first_name'];
        $order->last_name = $orderData['data']['shipping']['last_name'];
        $order->initials = $order->first_name ? strtoupper($order->first_name[0]) . '.' : '';
        $order->gender = $orderData['data']['customer']['gender'] ? $orderData['data']['customer']['gender'][0] : '';
        $order->email = $orderData['data']['customer']['email'];
        $order->phone_number = $orderData['data']['customer']['phone'];
        $order->street = $orderData['data']['shipping']['street'];
        $order->house_nr = $orderData['data']['shipping']['house_number'] . ($orderData['data']['shipping']['house_number_ext'] ? ' ' . $orderData['data']['shipping']['house_number_ext'] : '');
        $order->zip_code = $orderData['data']['shipping']['zip_code'];
        $order->city = $orderData['data']['shipping']['city'];
        $order->country = $orderData['data']['shipping']['country_code'];
        $order->company_name = $orderData['data']['shipping']['company'];
        $order->invoice_first_name = $orderData['data']['billing']['first_name'];
        $order->invoice_last_name = $orderData['data']['billing']['last_name'];
        $order->invoice_street = $orderData['data']['billing']['street'];
        $order->invoice_house_nr = $orderData['data']['billing']['house_number'] . ($orderData['data']['billing']['house_number_ext'] ? ' ' . $orderData['data']['billing']['house_number_ext'] : '');
        $order->invoice_zip_code = $orderData['data']['billing']['zip_code'];
        $order->invoice_city = $orderData['data']['billing']['city'];
        $order->invoice_country = $orderData['data']['billing']['country_code'];
        $order->invoice_id = strtoupper($orderData['channel_name'] . '-' . $orderData['channel_id']);
        $order->order_origin = $orderData['channel_name'];

        $order->total = $orderData['data']['price']['total'];
        $order->subtotal = $orderData['data']['price']['subtotal'];
        $order->discount = $orderData['data']['price']['discount'];
        $order->fulfillment_status = $orderData['status_shipped'] != 'shipped' ? 'unhandled' : $orderData['status_shipped'];
        $order->save();

        $channableOrder = new ChannableOrder();
        $channableOrder->order_id = $order->id;
        $channableOrder->channable_id = $orderData['id'];
        $channableOrder->project_id = $orderData['project_id'];
        $channableOrder->platform_id = $orderData['platform_id'];
        $channableOrder->platform_name = $orderData['platform_name'];
        $channableOrder->channel_id = $orderData['channel_id'];
        $channableOrder->channel_name = $orderData['channel_name'];
        $channableOrder->status_paid = $orderData['status_paid'];
        $channableOrder->status_shipped = $orderData['status_shipped'];
        $channableOrder->tracking_code = $orderData['tracking_code'];
        $channableOrder->tracking_original = $orderData['tracking_original'];
        $channableOrder->transporter = $orderData['transporter'];
        $channableOrder->transporter_original = $orderData['transporter_original'];
        $channableOrder->status_paid = $orderData['id'];
        $channableOrder->status_shipped = $orderData['id'];
        $channableOrder->commission = $orderData['data']['price']['commission'];
        $channableOrder->save();

        foreach ($orderData['data']['products'] as $product) {
            $thisProduct = Product::publicShowable()->where('ean', $product['ean'])->first();
            $orderProduct = new OrderProduct();
            $orderProduct->quantity = $product['quantity'];
            $orderProduct->product_id = $thisProduct->id ?? null;
            $orderProduct->order_id = $order->id;
            $orderProduct->name = $thisProduct->name ?? $product['title'];
            $orderProduct->price = $product['price'] * $orderProduct->quantity;
            $orderProduct->discount = $product['discount'];
            $orderProduct->sku = $thisProduct->sku ?? '';
            $orderProduct->save();
        }

        if ($orderData['data']['price']['transaction_fee']) {
            $orderProduct = new OrderProduct();
            $orderProduct->quantity = 1;
            $orderProduct->product_id = null;
            $orderProduct->order_id = $order->id;
            $orderProduct->name = $orderData['data']['price']['payment_method'];
            $orderProduct->price = $orderData['data']['price']['transaction_fee'];
            $orderProduct->discount = 0;
            $orderProduct->product_extras = json_encode([]);
            $orderProduct->sku = 'payment_costs';
            $orderProduct->save();
        }

        if ($orderData['data']['price']['shipping']) {
            $orderProduct = new OrderProduct();
            $orderProduct->quantity = 1;
            $orderProduct->product_id = null;
            $orderProduct->order_id = $order->id;
            $orderProduct->name = 'Verzending';
            $orderProduct->price = $orderData['data']['price']['shipping'];
            $orderProduct->discount = 0;
            $orderProduct->product_extras = json_encode([]);
            $orderProduct->sku = 'shipping_costs';
            $orderProduct->save();
        }

        $orderPayment = new OrderPayment();
        $orderPayment->amount = $order->total;
        $orderPayment->order_id = $order->id;
        $orderPayment->psp = $orderData['data']['price']['payment_method'];
        $orderPayment->payment_method = $orderData['data']['price']['payment_method'];
        $orderPayment->status = 'paid';
        $orderPayment->save();

        $orderLog = new OrderLog();
        $orderLog->order_id = $order->id;
        $orderLog->tag = 'order.created.by.channable';
        $orderLog->save();

        $orderLog = new OrderLog();
        $orderLog->order_id = $order->id;
        $orderLog->note = $orderData['data']['extra']['memo'];
        $orderLog->tag = 'order.note.created';
        $orderLog->save();

        if ($orderData['status_paid'] == 'paid') {
            $order->changeStatus('paid');
        }
    }

    public static function syncStock()
    {
        $channableApiKey = Customsetting::get('channable_api_key');
        $channableCompanyId = Customsetting::get('channable_company_id');
        $channableProjectId = Customsetting::get('channable_project_id');
        if ($channableApiKey && $channableCompanyId && $channableProjectId) {
            Product::publicShowable()->chunk(50, function ($products) {
                $channableApiKey = Customsetting::get('channable_api_key');
                $channableCompanyId = Customsetting::get('channable_company_id');
                $channableProjectId = Customsetting::get('channable_project_id');

                $channableProducts = [];
                foreach ($products as $product) {
                    $channableProducts[] = [
                        'id' => $product->id,
                        'title' => $product->name,
                        'price' => (float)$product->currentPrice,
                        'stock' => $product->directSellableStock() < 0 ? 0 : $product->directSellableStock(),
                    ];
                }

                try {
                    $response = Http::withToken($channableApiKey)
                        ->retry(5, 5000)
                        ->post(self::APIURL . '/companies/' . $channableCompanyId . '/projects/' . $channableProjectId . '/offers', $channableProducts)
                        ->json();
                } catch (Exception $exception) {
                    $response = null;
                }
            });
        }
    }
}
