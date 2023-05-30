<?php

use App\Helpers\ResponseError;

$e = new ResponseError;

return [

    /*
    |--------------------------------------------------------------------------
    | Pagination Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used by the paginator library to build
    | the simple pagination links. You are free to change them to anything
    | you want to customize your views to better match your application.
    |
    */

    $e::NO_ERROR  => 'Успешно',
    $e::ERROR_100 => 'Пользователь не авторизован.',
    $e::ERROR_101 => 'У пользователя нет нужных ролей.',
    $e::ERROR_102 => 'Логин или пароль неверный.',
    $e::ERROR_103 => 'Адрес электронной почты пользователя не подтвержден.',
    $e::ERROR_104 => 'Номер телефона пользователя не подтвержден.',
    $e::ERROR_105 => 'Учетная запись пользователя не подтверждена.',
    $e::ERROR_106 => 'Пользователь уже существует.',
    $e::ERROR_107 => 'Пожалуйста, войдите, используя facebook или google.',
    $e::ERROR_108 => 'У пользователя нет кошелька.',
    $e::ERROR_109 => 'Недостаточно средств на кошельке.',
    $e::ERROR_110 => 'Невозможно обновить эту роль пользователя.',
    $e::ERROR_111 => 'Вы можете купить только :quantity продуктов.',
    $e::ERROR_112 => 'когда статус: :verify вы должны добавить текст $verify_code в тело и альтернативное тело',
    $e::ERROR_113 => 'У курьера нет эл. кошелька',
    $e::ERROR_114 => 'У продавца нет эл. кошелька',
    $e::ERROR_115 => 'Неправильный номер телефона',

    $e::ERROR_201 => 'Неверный одноразовый пароль',
    $e::ERROR_202 => 'Слишком много запросов, попробуйте позже',
    $e::ERROR_203 => 'Срок действия OTP-кода истек',

    $e::ERROR_204 => 'Вы еще не продавец или ваш магазин не создан',
    $e::ERROR_205 => 'Магазин уже создан',
    $e::ERROR_206 => 'У пользователя уже есть Магазин',
    $e::ERROR_207 => 'Не могу обновить продавца магазина',
    $e::ERROR_208 => 'Подписка уже активна',
    $e::ERROR_209 => 'Зона доставки магазина уже создана',
    $e::ERROR_210 => 'Доставка уже прикреплена',
    $e::ERROR_211 => 'неверный доставщик или токен не найден',
    $e::ERROR_212 => 'Не ваш магазин. Проверьте свой другой аккаунт',
    $e::ERROR_213 => 'Срок действия вашей подписки истек.',
    $e::ERROR_214 => 'Срок действия вашего лимита продуктов по подписке истек',
    $e::ERROR_215 => 'Неверный код или срок действия токена истек',
    $e::ERROR_216 => 'Подтвердить код отправить',
    $e::ERROR_217 => 'Пользователь отправляет электронное письмо',

    $e::ERROR_249 => 'Недействительный купон',
    $e::ERROR_250 => 'Срок действия купона истек',
    $e::ERROR_251 => 'Купон уже использован',
    $e::ERROR_252 => 'Статус уже использован',
    $e::ERROR_253 => 'Неверный тип статуса',
    $e::ERROR_254 => 'Не удается обновить статус отмены',
    $e::ERROR_255 => 'Невозможно обновить статус заказа, если заказ уже в пути или доставлен',

    $e::ERROR_400 => 'Плохой запрос.',
    $e::ERROR_401 => 'Неавторизованный.',
    $e::ERROR_403 => 'Ваш проект не активирован.',
    $e::ERROR_404 => 'Товар не найден.',
    $e::ERROR_415 => 'Нет связи с базой данных',
    $e::ERROR_422 => 'Ошибка проверки',
    $e::ERROR_429 => 'Слишком много запросов',
    $e::ERROR_430 => 'Количество на складе 0',
    $e::ERROR_431 => 'Активная валюта по умолчанию не найдена',
    $e::ERROR_432 => 'Неопределенный тип',

    $e::ERROR_501 => 'Ошибка при создании',
    $e::ERROR_502 => 'Ошибка при обновлении',
    $e::ERROR_503 => 'Ошибка при удалении.',
    $e::ERROR_504 => 'Невозможно удалить запись со значениями.',
    $e::ERROR_505 => 'Невозможно удалить запись по умолчанию. :ids',
    $e::ERROR_506 => 'Уже существует.',
    $e::ERROR_507 => 'Невозможно удалить запись с продуктами.',
    $e::ERROR_508 => 'Неверный формат Excel или неверные данные.',
    $e::ERROR_509 => 'Неверный формат даты.',
    $e::ERROR_510 => 'Адрес правильный.',


    $e::CONFIRMATION_CODE               => 'Код подтверждения :code',
    $e::NEW_ORDER                       => 'Новый заказ для тебя # :id',
    $e::PHONE_OR_EMAIL_NOT_FOUND        => 'Телефон или электронная почта не найдена',
    $e::ORDER_NOT_FOUND                 => 'Заказ не найден',
    $e::ORDER_REFUNDED                  => 'Заказ возвращен',
    $e::ORDER_PICKUP                    => 'Заказ самовывоз',
    $e::SHOP_NOT_FOUND                  => 'Магазин не найден',
    $e::OTHER_SHOP                      => 'Другой магазин',
    $e::SHOP_OR_DELIVERY_ZONE           => 'Пустой магазин или зона доставки',
    $e::NOT_IN_POLYGON                  => 'Не в полигоне',
    $e::CURRENCY_NOT_FOUND              => 'Валюта не найдена',
    $e::LANGUAGE_NOT_FOUND              => 'Язык не найден',
    $e::CANT_DELETE_ORDERS              => 'Не могу удалить заказы :ids',
    $e::CANT_UPDATE_ORDERS              => 'Не могу обновить заказы :ids',
    $e::STATUS_CHANGED                  => 'Статус вашего заказа изменен на :status',
    $e::ADD_CASHBACK                    => 'Добавлен кэшбек',
    $e::PAYOUT_ACCEPTED                 => 'Выплата уже :status',
    $e::CANT_DELETE_IDS                 => 'Не могу удалить :ids',
    $e::USER_NOT_FOUND                  => 'Пользователь не найден',
    $e::USER_IS_BANNED                  => 'Пользователь забанен!',
    $e::INCORRECT_LOGIN_PROVIDER        => 'Пожалуйста, войдите, используя facebook или google.',
    $e::FIN_FO                          => 'Вам нужно расширение информации о файле php',
    $e::USER_SUCCESSFULLY_REGISTERED    => 'Пользователь успешно зарегистрирован',
    $e::USER_CARTS_IS_EMPTY             => 'Корзины пользователей пусты',
    $e::PRODUCTS_IS_EMPTY               => 'Товары пусты',
    $e::RECORD_WAS_SUCCESSFULLY_CREATED => 'Запись успешно создана',
    $e::RECORD_WAS_SUCCESSFULLY_UPDATED => 'Запись успешно обновлена',
    $e::RECORD_WAS_SUCCESSFULLY_DELETED => 'Запись успешно удалена',
    $e::IMAGE_SUCCESSFULLY_UPLOADED     => 'Успех :title, :type',
    $e::EMPTY_STATUS                    => 'Статус пуст',
    $e::SUCCESS                         => 'Успех',
    $e::DELIVERYMAN_IS_NOT_CHANGED      => 'Вам нужно сменить курьера',
    $e::CATEGORY_IS_PARENT              => 'Категория является родительской',
    $e::ATTACH_FOR_ADDON                => 'Нельзя прикреплять товары для аддона',
    $e::TYPE_PRICE_USER                 => 'Тип, цена или пользователь пусты',
    $e::NOTHING_TO_UPDATE               => 'Нечего обновлять',
    $e::WAITER_NOT_EMPTY                => 'Официант уже закреплён',
    $e::EMPTY                           => 'Не указано',

    $e::ORDER_OR_DELIVERYMAN_IS_EMPTY   => 'Заказ не найден или курьер не прикреплен',
    $e::TABLE_BOOKING_EXISTS            => 'У этого стола уже есть бронь. От :start_date до :end_date',
    $e::DELIVERYMAN_SETTING_EMPTY       => 'У вас не заданы настройки',
    $e::NEW_BOOKING                     => 'Новая бронь',
];
