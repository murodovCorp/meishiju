<?php

declare(strict_types=1);

use Yansongda\Pay\Pay;

return [
    'alipay' => [
        //线上配置
        'default' => [
            // 必填-支付宝分配的 app_id
            'app_id' => '2021003193642628',
            // 必填-应用私钥 字符串或路径
            //
            // 在 https://open.alipay.com/develop/manage 《应用详情->开发设置->接口加签方式》中设置
            'app_secret_cert' => 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCRHN+q83BMkLGkhfy4EHFnzDEQm17/6LsGhdIS218Qjy4Wf/t+S1AuPxf+CnzVOVgkqanoW2aI6xpmHLdb8eFlSIv2Fu3CZ5k2ni0CdrYO5s/959U8LTTLeIJXGUZrnWIi0jjZJn1//jN6DCl67ComcvDZbhByhBuv49efjVj+JcAk0v3x+YlymX8rlHtBaLgrMC8qJ31Qs6ioRf8M6h8cCwgptDXoZj+FbC0C/n6RuioFpl48I5P6aZsbKK5Mz4jek/QN+l1zLNVa+odlXK061bAUv2PywQkietfkojlS98ak4Ys5Q0UmAm0K2ir5MRR0GrEor4pWH0CELdpxSJkvAgMBAAECggEAfxiC7f95hBXXAPo5dh0wtnCpKJpsgkccR4I9lDZ+9Mny29IMO3lLKLYqFV6qJzaqdLIFFnW3QIRqArV0Axu/Bi6cJ1W9uF90WdoAIgBHRJXz5bZ/lM0jSHKr9g01GFwn78ef8tE7ajcCbwrn6bme0T4qpMU5spLyjQNJf30VTMIBdRaLcrDplB4vv8FXg+8DK/2Ax3EZV5NmFQ82o5gny2ATUcy94OafcoUczJdtHvx66v+NTj7l7HQJdkqerL1HG6+ocm2FUQACTf7TFWW+72SGFptI8DXklZw/ooOEV27qqnxlpO2DozLSZlrZA64APuqgo+YkZLmu0PAUX0njgQKBgQDL/aWUgNLrMy/5g0VGBaisiUcSunUAz1VAZTOuyS5hR8lXFznYy4zQSSOAGlD9ovGcAOZRIIA+hDNN2cL8YZXaFSz75rVNszjaX/wx++eQwhFPqdJOqsBR8FUd18uFnWBihILxJkyhj7S5/fsAIXZdcxKhfMr/KR7uT84iK9LQWQKBgQC2HEuPAcafFOIjFZc2WgNOICQ17NEPZQSl8yA/aU8nY4u7e3JWWBfD04tdA9i9TzfCU7KiZovNb9EWp9CRhleHf9YzDQTN0iA1SiAeBMQxdsoPlvvthM5Hd8o4pXYWUGz1em26twDgYbgI7OpNWe2ThEiJM8P3jqD4R9wcP3lExwKBgQCcycQutCVpadqB6Qehnk/a7ViXaIDtsm5QJHjo6vCfDNIQAmYP38PHg6+pkpCVolwaEAb2bAimBG56c9mOqmLHUGdW5iiMJCiakBQDRx7hAIjVti1TEaLoV9TULzza4e9nSXprZ4iW1SGPo0r8+rtDcGLthEFyhWSkvuotEYnjYQKBgBm1fOU/du7WX+ZcEfDWXEkXOIV0XT8zH6jd3duV3lrNLtjU4JtX7GL05mPHOz3ehRyl/D35BBuIgZgVMNiagpjabz7dw6Pa2jkrscULHzdscbIpun8ZlJV6T84XbchQqlEta3hEhZcntHduGtW4sFV3A1Z7PFsig48JdgV24IPZAoGAY3xrLzhh9NDh1QRtdWTD/f9VwLM38OYRjGiQggHcZg9IbLu+eoQbvwbiHzR0bPKb8FziZKTL0euojAemJiQR0YidrcYqykxkucvgysuOcod4eZrn7oqUdau5cF1tfXXMzqDmgAcujxH9vHvm7Xp9ZpfnTvpM6umX7Uwi7pO6T8o=',
            // 必填-应用公钥证书 路径
            // 设置应用私钥后，即可下载得到以下3个证书
            'app_public_cert_path' => storage_path('app/public/alipayOnline/appCertPublicKey_2021003193642628.crt'),
            // 必填-支付宝公钥证书 路径
            'alipay_public_cert_path' => storage_path('app/public/alipayOnline/alipayCertPublicKey_RSA2.crt'),
            // 必填-支付宝根证书 路径
            'alipay_root_cert_path' => storage_path('app/public/alipayOnline/alipayRootCert.crt'),
            'return_url' => "",
            'notify_url' => "https://waimaiapi.meishiju.co/api/alipay-notify",
            // 选填-第三方应用授权token
            'app_auth_token' => '',
            // 选填-服务商模式下的服务商 id，当 mode 为 Pay::MODE_SERVICE 时使用该参数
            'service_provider_id' => '',
            // 选填-默认为正常模式。可选为： MODE_NORMAL, MODE_SANDBOX, MODE_SERVICE
            'mode' => Pay::MODE_NORMAL,
        ],
        //沙箱配置
        //'default' => [
        //    // 必填-支付宝分配的 app_id
        //    'app_id' => '9021000122687162',
        //    // 必填-应用私钥 字符串或路径
        //    //
        //    // 在 https://open.alipay.com/develop/manage 《应用详情->开发设置->接口加签方式》中设置
        //    'app_secret_cert' => 'MIIEowIBAAKCAQEAo9zSEblmFp3qAJywrwIjFYp1o/pwGzia+pEr3DokUejXIomcOSuzQ2GzVGXCZMF+BT4FEUIk49OVfiJxULCayA3yiVnIXnLNLQ3pkp+HusxCWlEb99ZizztnPOtPr40hcF2JdE4Cs8QTiMV1+840ovM6rPiw5xiW2BzYOpoUQ7jaiVts6hlWJoa0liyhBzU/vEq1pDkUIUsTKSlGBbCS5FdwRChjQrEQgWcdx5YBCFkuPNEjGaHfEa6pOwDf8mujfNUp/OyA4zPR/eQoB8atFh/OhROMTulBThyxvuIc1DYuj2M02X0sv0thqs02AzN94RpHf+ao9+pB51xq0b2clQIDAQABAoIBAARgxwAQOwkJqRHhHdtfLD7MFz6NV2dyyLk/HbPhnczOcskKcjEY2M5JEviDS4FTpiFPueTvJnsr0QewteRZA5mqvbsrue3EFXVJtN7lAkRcZr1xA1VI1hUBWk3HX7aqB6Wv6hdAalQRPhBebjuPE9rysrOiZV+XSZh2lNRj/3ybrdDpFSVTgGf/v/iOs2ML9jXKERCUtSko5s8wNIdCNcxB/T+EGr3H6oIJbZULvyPlAhzbU38//A9ZScF63h8ynhMbDWLPKZ+j4paQCbVQ7bCX4Lh+X/aynBAjs8taKf6sL6vDiR3fYUOM6CUC1wGfz1JPuyuMQE2IMVo7vliYVCECgYEA0C9onKzcDgORonO7RjiGPuAX3UzhSeBJ8xDNMB4nBmBGB1rhhh4MRGojLgtxUCXQMVuYzbY5v3muIavClZb4NAsGB6J6EJFIPacUxs2KZbXUudk5fM4aI7V381hYaXTQQ5iJOe4EKQ0QwLKRenfW3HjdE/+JTwCAdQosqIyeWOkCgYEAyX9kHKSn5vww3zYU9oyyCTrlq/J/J/0oT+Pe+lnCacwtQq24kk3sP3OHPsLSDzzdyLSQcRUePvp9zzuGR7dN0lFVbA84CnIoEuJ84dVouTD8q6TR89w/bxFLbIT0OzflpfbSP7iq/uG4QtPUzcB1yQKe8KVwO7v7lKj6dOEq2s0CgYAUkYqJaD65l7qszThkgLMqxSM2dyEPFnzX6gILzf7XD237zgvYH2Hg5IejRfglDgdayQz4zhc4hsIgi4LHGspdAfXPjUr4FhKIHNjdp3MUB3oD/qVCYm6MtqIqRcE+cg8djpWIRq7ci3DrSPk8m1h8Iejdz/J1/ruyVJYC9Rmz+QKBgEa7v4sGiiJd2VIiDA3Yqg7va/yGbfi9t88DsRy1MbguAp1rmmVRkWNczNdNUKwks75nFGV+AlYdXNjIjoTSZjE0eAYs+YFBPawTcb9dSRvphGlEvKccY/A7Y/y+V3YKU0WFdZ0E2JIM7sAW2Jc5vp9Hmj/j85gkj1ZKU1GAUajpAoGBAIi0sLcbqY6XZAQY8WUqW+zd/rXV5P9DYb+6jFedEePVDKSk4NFt6rxn1tNHp6PvJhzGfcWagCea6QSV6/rtqq5JjHx6xjIVdMMYkYfdZaoojA5YV3DRUTOh9IwK7YNgSzGZ2DRa3Adbt7iic+mulPwGou/oEtqB+cdMgA01VX1I',
        //    // 必填-应用公钥证书 路径
        //    // 设置应用私钥后，即可下载得到以下3个证书
        //    'app_public_cert_path' => storage_path('app/public/appPublicCert.crt'),
        //    // 必填-支付宝公钥证书 路径
        //    'alipay_public_cert_path' => storage_path('app/public/alipayPublicCert.crt'),
        //    // 必填-支付宝根证书 路径
        //    'alipay_root_cert_path' => storage_path('app/public/alipayRootCert.crt'),
        //    'return_url' => "",
        //    'notify_url' => "https://waimaiapi.meishiju.co/api/alipay-notify",
        //    // 选填-第三方应用授权token
        //    'app_auth_token' => '',
        //    // 选填-服务商模式下的服务商 id，当 mode 为 Pay::MODE_SERVICE 时使用该参数
        //    'service_provider_id' => '',
        //    // 选填-默认为正常模式。可选为： MODE_NORMAL, MODE_SANDBOX, MODE_SERVICE
        //    'mode' => Pay::MODE_SANDBOX,
        //],
        /*'default' => [
            // 必填-支付宝分配的 app_id
            'app_id' => '2016082000295641',
            // 必填-应用私钥 字符串或路径
            // 在 https://open.alipay.com/develop/manage 《应用详情->开发设置->接口加签方式》中设置
            'app_secret_cert' => 'MIIEowIBAAKCAQEAo9zSEblmFp3qAJywrwIjFYp1o/pwGzia+pEr3DokUejXIomcOSuzQ2GzVGXCZMF+BT4FEUIk49OVfiJxULCayA3yiVnIXnLNLQ3pkp+HusxCWlEb99ZizztnPOtPr40hcF2JdE4Cs8QTiMV1+840ovM6rPiw5xiW2BzYOpoUQ7jaiVts6hlWJoa0liyhBzU/vEq1pDkUIUsTKSlGBbCS5FdwRChjQrEQgWcdx5YBCFkuPNEjGaHfEa6pOwDf8mujfNUp/OyA4zPR/eQoB8atFh/OhROMTulBThyxvuIc1DYuj2M02X0sv0thqs02AzN94RpHf+ao9+pB51xq0b2clQIDAQABAoIBAARgxwAQOwkJqRHhHdtfLD7MFz6NV2dyyLk/HbPhnczOcskKcjEY2M5JEviDS4FTpiFPueTvJnsr0QewteRZA5mqvbsrue3EFXVJtN7lAkRcZr1xA1VI1hUBWk3HX7aqB6Wv6hdAalQRPhBebjuPE9rysrOiZV+XSZh2lNRj/3ybrdDpFSVTgGf/v/iOs2ML9jXKERCUtSko5s8wNIdCNcxB/T+EGr3H6oIJbZULvyPlAhzbU38//A9ZScF63h8ynhMbDWLPKZ+j4paQCbVQ7bCX4Lh+X/aynBAjs8taKf6sL6vDiR3fYUOM6CUC1wGfz1JPuyuMQE2IMVo7vliYVCECgYEA0C9onKzcDgORonO7RjiGPuAX3UzhSeBJ8xDNMB4nBmBGB1rhhh4MRGojLgtxUCXQMVuYzbY5v3muIavClZb4NAsGB6J6EJFIPacUxs2KZbXUudk5fM4aI7V381hYaXTQQ5iJOe4EKQ0QwLKRenfW3HjdE/+JTwCAdQosqIyeWOkCgYEAyX9kHKSn5vww3zYU9oyyCTrlq/J/J/0oT+Pe+lnCacwtQq24kk3sP3OHPsLSDzzdyLSQcRUePvp9zzuGR7dN0lFVbA84CnIoEuJ84dVouTD8q6TR89w/bxFLbIT0OzflpfbSP7iq/uG4QtPUzcB1yQKe8KVwO7v7lKj6dOEq2s0CgYAUkYqJaD65l7qszThkgLMqxSM2dyEPFnzX6gILzf7XD237zgvYH2Hg5IejRfglDgdayQz4zhc4hsIgi4LHGspdAfXPjUr4FhKIHNjdp3MUB3oD/qVCYm6MtqIqRcE+cg8djpWIRq7ci3DrSPk8m1h8Iejdz/J1/ruyVJYC9Rmz+QKBgEa7v4sGiiJd2VIiDA3Yqg7va/yGbfi9t88DsRy1MbguAp1rmmVRkWNczNdNUKwks75nFGV+AlYdXNjIjoTSZjE0eAYs+YFBPawTcb9dSRvphGlEvKccY/A7Y/y+V3YKU0WFdZ0E2JIM7sAW2Jc5vp9Hmj/j85gkj1ZKU1GAUajpAoGBAIi0sLcbqY6XZAQY8WUqW+zd/rXV5P9DYb+6jFedEePVDKSk4NFt6rxn1tNHp6PvJhzGfcWagCea6QSV6/rtqq5JjHx6xjIVdMMYkYfdZaoojA5YV3DRUTOh9IwK7YNgSzGZ2DRa3Adbt7iic+mulPwGou/oEtqB+cdMgA01VX1I',
            // 必填-应用公钥证书 路径
            // 设置应用私钥后，即可下载得到以下3个证书
            'app_public_cert_path' => storage_path('app/public/appPublicCert.crt'),
            // 必填-支付宝公钥证书 路径
            'alipay_public_cert_path' => storage_path('app/public/alipayPublicCert.crt'),
            // 必填-支付宝根证书 路径
            'alipay_root_cert_path' => storage_path('app/public/alipayRootCert.crt'),
            'return_url' => "https://9112-213-230-97-92.ngrok-free.app/api/v1/order-alipay-success",
            'notify_url' => "https://9112-213-230-97-92.ngrok-free.app/api/v1/order-alipay-success",
            // 选填-第三方应用授权token
            'app_auth_token' => '',
            // 选填-服务商模式下的服务商 id，当 mode 为 Pay::MODE_SERVICE 时使用该参数
            'service_provider_id' => '',
            // 选填-默认为正常模式。可选为： MODE_NORMAL, MODE_SANDBOX, MODE_SERVICE
            'mode' => Pay::MODE_SANDBOX,
        ],*/
    ],
    'wechat' => [
        'default' => [
            /*
                  v1：2E1641C482B5B5B0B29E15131881DF5768B16A63
                  v2：4FXyBb3NgYSTfxsFyicS57jxOLDS2TlG
                  v3：4FXyBb3NgYSTfxsFyicS57jxOLDS2TlG
            */
            // 必填-商户号，服务商模式下为服务商商户号
            'mch_id' => '1650190961',
            // 选填-v2商户私钥
            'mch_secret_key_v2' => '4FXyBb3NgYSTfxsFyicS57jxOLDS2TlG',
            // 必填-商户秘钥
            'mch_secret_key' => '4FXyBb3NgYSTfxsFyicS57jxOLDS2TlG',
            // 必填-商户私钥 字符串或路径
            'mch_secret_cert' => storage_path('app/public/wechatOnline/apiclient_key.pem'),
            // 必填-商户公钥证书路径
            'mch_public_cert_path' => storage_path('app/public/wechatOnline/apiclient_cert.pem'),
            // 必填
            'notify_url' => 'https://waimaiapi.meishiju.co/api/wechat-notify',
            // 选填-公众号 的 app_id
            'mp_app_id' => 'wx1a246ef9635faea5',
            // 选填-小程序 的 app_id
            'mini_app_id' => '',
            // 选填-app 的 app_id
            'app_id' => 'wx1a246ef9635faea5',
            // 选填-合单 app_id
            'combine_app_id' => '',
            // 选填-合单商户号
            'combine_mch_id' => '',
            // 选填-服务商模式下，子公众号 的 app_id
            'sub_mp_app_id' => '',
            // 选填-服务商模式下，子 app 的 app_id
            'sub_app_id' => '',
            // 选填-服务商模式下，子小程序 的 app_id
            'sub_mini_app_id' => '',
            // 选填-服务商模式下，子商户id
            'sub_mch_id' => '',
            // 选填-微信公钥证书路径, optional，强烈建议 php-fpm 模式下配置此参数
            'wechat_public_cert_path' => [
                //'45F59D4DABF31918AFCEC556D5D2C6E376675D57' => __DIR__.'/Cert/wechatPublicKey.crt',
            ],
            // 选填-默认为正常模式。可选为： MODE_NORMAL, MODE_SERVICE
            'mode' => Pay::MODE_NORMAL,
        ],
    ],
//    'unipay' => [
//        'default' => [
//            // 必填-商户号
//            'mch_id' => '',
//            // 必填-商户公私钥
//            'mch_cert_path' => '',
//            // 必填-商户公私钥密码
//            'mch_cert_password' => '000000',
//            // 必填-银联公钥证书路径
//            'unipay_public_cert_path' => '',
//            // 必填
//            'return_url' => '',
//            // 必填
//            'notify_url' => '',
//        ],
//    ],
    'http' => [ // optional
        'timeout' => 5.0,
        'connect_timeout' => 5.0,
        // 更多配置项请参考 [Guzzle](https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html)
    ],
    // optional，默认 warning；日志路径为：sys_get_temp_dir().'/logs/yansongda.pay.log'
    'logger' => [
        'enable' => true,
        'file' => storage_path('logs/alipay.log'),
        'level' => 'debug',
        'type' => 'single', // optional, 可选 daily.
        'max_file' => 30,
    ],
];