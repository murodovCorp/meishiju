<?php

namespace App\Http\Controllers\API\v1\Rest;

set_time_limit(86400);

use App\Http\Controllers\Controller;
use App\Models\ActiveReferral;
use App\Models\AssignShopTag;
use App\Models\BackupHistory;
use App\Models\Banner;
use App\Models\BannerShop;
use App\Models\BannerTranslation;
use App\Models\Blog;
use App\Models\BlogTranslation;
use App\Models\Bonus;
use App\Models\Booking\Booking;
use App\Models\Booking\ShopSection;
use App\Models\Booking\ShopSectionTranslation;
use App\Models\Booking\Table;
use App\Models\Booking\UserBooking;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\Coupon;
use App\Models\CouponTranslation;
use App\Models\Currency;
use App\Models\DeliveryManSetting;
use App\Models\DeliveryTranslation;
use App\Models\DeliveryZone;
use App\Models\Discount;
use App\Models\EmailSetting;
use App\Models\EmailSubscription;
use App\Models\EmailTemplate;
use App\Models\ExtraGroup;
use App\Models\ExtraGroupTranslation;
use App\Models\ExtraValue;
use App\Models\Faq;
use App\Models\FaqTranslation;
use App\Models\Gallery;
use App\Models\Invitation;
use App\Models\Language;
use App\Models\Like;
use App\Models\MetaTag;
use App\Models\ModelLog;
use App\Models\Notification;
use App\Models\NotificationUser;
use App\Models\Order;
use App\Models\OrderCoupon;
use App\Models\OrderDetail;
use App\Models\OrderRefund;
use App\Models\OrderStatus;
use App\Models\Payment;
use App\Models\PaymentPayload;
use App\Models\PaymentProcess;
use App\Models\Payout;
use App\Models\Point;
use App\Models\PointHistory;
use App\Models\PrivacyPolicy;
use App\Models\PrivacyPolicyTranslation;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductDiscount;
use App\Models\ProductExtra;
use App\Models\ProductProperties;
use App\Models\ProductTranslation;
use App\Models\Receipt;
use App\Models\ReceiptIngredient;
use App\Models\ReceiptInstruction;
use App\Models\ReceiptNutrition;
use App\Models\ReceiptNutritionTranslation;
use App\Models\ReceiptStock;
use App\Models\ReceiptTranslation;
use App\Models\Referral;
use App\Models\ReferralTranslation;
use App\Models\Review;
use App\Models\Settings;
use App\Models\Shop;
use App\Models\ShopCategory;
use App\Models\ShopClosedDate;
use App\Models\ShopDeliverymanSetting;
use App\Models\ShopGallery;
use App\Models\ShopPayment;
use App\Models\ShopSubscription;
use App\Models\ShopTag;
use App\Models\ShopTagTranslation;
use App\Models\ShopTranslation;
use App\Models\ShopWorkingDay;
use App\Models\SmsGateway;
use App\Models\SmsPayload;
use App\Models\SocialProvider;
use App\Models\Stock;
use App\Models\StockAddon;
use App\Models\StockExtra;
use App\Models\Story;
use App\Models\Subscription;
use App\Models\Tag;
use App\Models\TagTranslation;
use App\Models\TermCondition;
use App\Models\TermConditionTranslation;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\Translation;
use App\Models\Unit;
use App\Models\UnitTranslation;
use App\Models\User;
use App\Models\UserCart;
use App\Models\UserPoint;
use App\Models\Wallet;
use App\Models\WalletHistory;
use App\Traits\ApiResponse;
use Artisan;
use Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{
    use ApiResponse;

    public function bosyaTest(Request $request)
    {
//        $this->createNewTranslations();
//        $this->ordersUpdate();
    }

    #region GIG LOGISTIC
    public function gigLogistic(): array
    {
        $headers = [
            'Content-Type' => 'application/json'
        ];

        $response = Http::withHeaders($headers)->post(
            'https://giglthirdpartyapitestenv.azurewebsites.net/api/thirdparty/login',
            [
                'username' => '',
                'Password' => '',
//                'SessionObj' => '',
            ]
        );

        $test = Http::withHeaders($headers)->post(
            'http://test.giglogisticsse.com/api/thirdparty/login',
            [
                'username' => '',
                'Password' => '',
//                'SessionObj' => '',
            ]
        );

        return [
            'production' => [
                'url'    => 'https://giglthirdpartyapitestenv.azurewebsites.net/api/thirdparty/login',
                'e_uri'  => $response->effectiveUri(),
                'status' => $response->status(),
                'body'   => json_decode($response->body()),
            ],
            'test' => [
                'url'    => 'http://test.giglogisticsse.com/api/thirdparty/login',
                'e_uri'  => $response->effectiveUri(),
                'status' => $test->status(),
                'body'   => json_decode($test->body()),
            ]
        ];
    }
    #endregion

    #region ORDERS UPDATE
    public function ordersUpdate() {

        $orders = Order::where('created_at', '>=' , date('Y-m-d', strtotime('2023-05-12')))
            ->get();

        foreach ($orders as $order) {
            $order->update([
                'deleted_at' => null
            ]);
        }

    }
    #endregion

    #region MODEL LOG DELETE
    public function modelLogDelete() {
        DB::table('model_log')->delete();
    }
    #endregion

    #region CREATE NEW TRANSLATIONS
    public function createNewTranslations() {

        $data = include_once base_path('app/Http/Controllers/API/v1/Rest/json.php');

        foreach ($data as $key => $value) {
            Translation::updateOrCreate([
                'key'       => $key,
                'locale'    => 'en'
            ], [
                'value'     => $value,
                'status'    => 1,
                'group'     => 'web',
            ]);
        }

    }
    #endregion

    #region TESTS
    public function tests() {
        //        --unit
        Artisan::call('make:test ActiveReferralTest');
        Artisan::call('make:test AssignShopTagTest');
        Artisan::call('make:test BackupHistoryTest');
        Artisan::call('make:test BannerTest');
        Artisan::call('make:test BannerShopTest');
        Artisan::call('make:test BannerTranslationTest');
        Artisan::call('make:test BlogTest');
        Artisan::call('make:test BlogTranslationTest');
        Artisan::call('make:test BonusTest');
        Artisan::call('make:test BranchTest');
        Artisan::call('make:test BranchTranslationTest');
        Artisan::call('make:test BrandTest');
        Artisan::call('make:test CareerTest');
        Artisan::call('make:test CareerTranslationTest');
        Artisan::call('make:test CartTest');
        Artisan::call('make:test CartDetailTest');
        Artisan::call('make:test CategoryTest');
        Artisan::call('make:test CategoryTranslationTest');
        Artisan::call('make:test ChatTest');
        Artisan::call('make:test ChatRequestTest');
        Artisan::call('make:test CouponTest');
        Artisan::call('make:test CouponTranslationTest');
        Artisan::call('make:test CurrencyTest');
        Artisan::call('make:test DeliveryManSettingTest');
        Artisan::call('make:test DeliveryTranslationTest');
        Artisan::call('make:test DeliveryZoneTest');
        Artisan::call('make:test DiscountTest');
        Artisan::call('make:test EmailSettingTest');
        Artisan::call('make:test EmailSubscriptionTest');
        Artisan::call('make:test EmailTemplateTest');
        Artisan::call('make:test ExtraGroupTest');
        Artisan::call('make:test ExtraGroupTranslationTest');
        Artisan::call('make:test ExtraValueTest');
        Artisan::call('make:test FaqTest');
        Artisan::call('make:test FaqTranslationTest');
        Artisan::call('make:test GalleryTest');
        Artisan::call('make:test InvitationTest');
        Artisan::call('make:test LandingPageTest');
        Artisan::call('make:test LanguageTest');
        Artisan::call('make:test LikeTest');
        Artisan::call('make:test MenuTest');
        Artisan::call('make:test MenuProductTest');
        Artisan::call('make:test MenuTranslationTest');
        Artisan::call('make:test MetaTagTest');
        Artisan::call('make:test ModelLogTest');
        Artisan::call('make:test NotificationTest');
        Artisan::call('make:test NotificationUserTest');
        Artisan::call('make:test OrderTest');
        Artisan::call('make:test OrderCouponTest');
        Artisan::call('make:test OrderDetailTest');
        Artisan::call('make:test OrderRefundTest');
        Artisan::call('make:test OrderStatusTest');
        Artisan::call('make:test PageTest');
        Artisan::call('make:test PageTranslationTest');
        Artisan::call('make:test PaymentTest');
        Artisan::call('make:test PaymentPayloadTest');
        Artisan::call('make:test PaymentProcessTest');
        Artisan::call('make:test PayoutTest');
        Artisan::call('make:test PointTest');
        Artisan::call('make:test PointHistoryTest');
        Artisan::call('make:test PrivacyPolicyTest');
        Artisan::call('make:test PrivacyPolicyTranslationTest');
        Artisan::call('make:test ProductTest');
        Artisan::call('make:test ProductAddonTest');
        Artisan::call('make:test ProductDiscountTest');
        Artisan::call('make:test ProductExtraTest');
        Artisan::call('make:test ProductPropertiesTest');
        Artisan::call('make:test ProductTranslationTest');
        Artisan::call('make:test PushNotificationTest');
        Artisan::call('make:test ReceiptTest');
        Artisan::call('make:test ReceiptIngredientTest');
        Artisan::call('make:test ReceiptInstructionTest');
        Artisan::call('make:test ReceiptNutritionTest');
        Artisan::call('make:test ReceiptNutritionTranslationTest');
        Artisan::call('make:test ReceiptStockTest');
        Artisan::call('make:test ReceiptTranslationTest');
        Artisan::call('make:test ReferralTest');
        Artisan::call('make:test ReferralTranslationTest');
        Artisan::call('make:test ReviewTest');
        Artisan::call('make:test SettingsTest');
        Artisan::call('make:test ShopTest');
        Artisan::call('make:test ShopCategoryTest');
        Artisan::call('make:test ShopClosedDateTest');
        Artisan::call('make:test ShopDeliverymanSettingTest');
        Artisan::call('make:test ShopGalleryTest');
        Artisan::call('make:test ShopPaymentTest');
        Artisan::call('make:test ShopSubscriptionTest');
        Artisan::call('make:test ShopTagTest');
        Artisan::call('make:test ShopTagTranslationTest');
        Artisan::call('make:test ShopTranslationTest');
        Artisan::call('make:test ShopWorkingDayTest');
        Artisan::call('make:test SmsGatewayTest');
        Artisan::call('make:test SmsPayloadTest');
        Artisan::call('make:test SocialProviderTest');
        Artisan::call('make:test StockTest');
        Artisan::call('make:test StockAddonTest');
        Artisan::call('make:test StockExtraTest');
        Artisan::call('make:test StoryTest');
        Artisan::call('make:test SubscriptionTest');
        Artisan::call('make:test TagTest');
        Artisan::call('make:test TagTranslationTest');
        Artisan::call('make:test TermConditionTest');
        Artisan::call('make:test TermConditionTranslationTest');
        Artisan::call('make:test TicketTest');
        Artisan::call('make:test TransactionTest');
        Artisan::call('make:test TranslationTest');
        Artisan::call('make:test UnitTest');
        Artisan::call('make:test UnitTranslationTest');
        Artisan::call('make:test UserTest');
        Artisan::call('make:test UserActivityTest');
        Artisan::call('make:test UserAddressTest');
        Artisan::call('make:test UserCartTest');
        Artisan::call('make:test UserPointTest');
        Artisan::call('make:test WalletTest');
        Artisan::call('make:test WalletHistoryTest');
        Artisan::call('make:test BookingTest');
        Artisan::call('make:test BookingShopTest');
        Artisan::call('make:test ShopBookingClosedDateTest');
        Artisan::call('make:test ShopBookingWorkingDayTest');
        Artisan::call('make:test ShopSectionTest');
        Artisan::call('make:test ShopSectionTranslationTest');
        Artisan::call('make:test TableTest');
        Artisan::call('make:test UserBookingTest');
    }
    #endregion

    #region MODELS
    public function allModels(): bool|string
    {
        $tables = collect([
            (new ActiveReferral)->getTable(),
            (new AssignShopTag)->getTable(),
            (new BackupHistory)->getTable(),
            (new Banner)->getTable(),
            (new BannerShop)->getTable(),
            (new BannerTranslation)->getTable(),
            (new Blog)->getTable(),
            (new BlogTranslation)->getTable(),
            (new Bonus)->getTable(),
            (new Booking)->getTable(),
            (new Brand)->getTable(),
            (new Cart)->getTable(),
            (new CartDetail)->getTable(),
            (new Category)->getTable(),
            (new CategoryTranslation)->getTable(),
            (new Coupon)->getTable(),
            (new CouponTranslation)->getTable(),
            (new Currency)->getTable(),
            (new DeliveryManSetting)->getTable(),
            (new DeliveryTranslation)->getTable(),
            (new DeliveryZone)->getTable(),
            (new Discount)->getTable(),
            (new EmailSetting)->getTable(),
            (new EmailSubscription)->getTable(),
            (new EmailTemplate)->getTable(),
            (new ExtraGroup)->getTable(),
            (new ExtraGroupTranslation)->getTable(),
            (new ExtraValue)->getTable(),
            (new Faq)->getTable(),
            (new FaqTranslation)->getTable(),
            (new Gallery)->getTable(),
            (new Invitation)->getTable(),
            (new Language)->getTable(),
            (new Like)->getTable(),
            (new MetaTag)->getTable(),
            (new Notification)->getTable(),
            (new NotificationUser)->getTable(),
            (new Order)->getTable(),
            (new OrderCoupon)->getTable(),
            (new OrderDetail)->getTable(),
            (new OrderRefund)->getTable(),
            (new OrderStatus)->getTable(),
            (new Payment)->getTable(),
            (new PaymentPayload)->getTable(),
            (new PaymentProcess)->getTable(),
            (new Payout)->getTable(),
            (new Point)->getTable(),
            (new PointHistory)->getTable(),
            (new PrivacyPolicy)->getTable(),
            (new PrivacyPolicyTranslation)->getTable(),
            (new Product)->getTable(),
            (new ProductAddon)->getTable(),
            (new ProductDiscount)->getTable(),
            (new ProductExtra)->getTable(),
            (new ProductProperties)->getTable(),
            (new ProductTranslation)->getTable(),
            (new Receipt)->getTable(),
            (new ReceiptIngredient)->getTable(),
            (new ReceiptInstruction)->getTable(),
            (new ReceiptNutrition)->getTable(),
            (new ReceiptNutritionTranslation)->getTable(),
            (new ReceiptStock)->getTable(),
            (new ReceiptTranslation)->getTable(),
            (new Referral)->getTable(),
            (new ReferralTranslation)->getTable(),
            (new Review)->getTable(),
            (new Settings)->getTable(),
            (new Shop)->getTable(),
            (new ShopCategory)->getTable(),
            (new ShopClosedDate)->getTable(),
            (new ShopDeliverymanSetting)->getTable(),
            (new ShopGallery)->getTable(),
            (new ShopPayment)->getTable(),
            (new ShopSection)->getTable(),
            (new ShopSectionTranslation)->getTable(),
            (new ShopSubscription)->getTable(),
            (new ShopTag)->getTable(),
            (new ShopTagTranslation)->getTable(),
            (new ShopTranslation)->getTable(),
            (new ShopWorkingDay)->getTable(),
            (new SmsGateway)->getTable(),
            (new SmsPayload)->getTable(),
            (new SocialProvider)->getTable(),
            (new Stock)->getTable(),
            (new StockAddon)->getTable(),
            (new StockExtra)->getTable(),
            (new Story)->getTable(),
            (new Subscription)->getTable(),
            (new Table)->getTable(),
            (new Tag)->getTable(),
            (new TagTranslation)->getTable(),
            (new TermCondition)->getTable(),
            (new TermConditionTranslation)->getTable(),
            (new Ticket)->getTable(),
            (new Transaction)->getTable(),
            (new Translation)->getTable(),
            (new Unit)->getTable(),
            (new UnitTranslation)->getTable(),
            (new User)->getTable(),
            (new UserBooking)->getTable(),
            (new UserCart)->getTable(),
            (new UserPoint)->getTable(),
            (new Wallet)->getTable(),
            (new WalletHistory)->getTable(),
        ]);

        $columns = collect();

        foreach ($tables as $table) {

            $list = DB::getSchemaBuilder()->getColumnListing($table);

            if (!empty($list)) {
                $columns->push(...$list);
            }

        }

        return json_encode($columns->unique()->values()->toArray());
    }
    #endregion
}
