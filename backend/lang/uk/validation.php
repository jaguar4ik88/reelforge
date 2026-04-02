<?php

return [
    'accepted'        => 'Поле :attribute має бути прийнято.',
    'email'           => 'Поле :attribute має бути дійсною адресою електронної пошти.',
    'max'             => [
        'string' => 'Поле :attribute не може перевищувати :max символів.',
        'file'   => 'Файл :attribute не може перевищувати :max кілобайт.',
        'array'  => 'Поле :attribute не може містити більше :max елементів.',
    ],
    'min'             => [
        'string' => 'Поле :attribute має містити щонайменше :min символів.',
        'array'  => 'Поле :attribute має містити щонайменше :min елементів.',
    ],
    'required'        => 'Поле :attribute обовʼязкове.',
    'string'          => 'Поле :attribute має бути рядком.',
    'unique'          => 'Такий :attribute вже існує.',
    'confirmed'       => 'Підтвердження поля :attribute не збігається.',
    'numeric'         => 'Поле :attribute має бути числом.',
    'exists'          => 'Вибране значення поля :attribute недійсне.',
    'mimes'           => 'Поле :attribute має бути файлом типу: :values.',
    'array'           => 'Поле :attribute має бути масивом.',
    'between'         => [
        'array' => 'Поле :attribute має містити від :min до :max елементів.',
    ],

    'attributes' => [
        'name'                  => 'імʼя',
        'email'                 => 'електронна пошта',
        'password'              => 'пароль',
        'title'                 => 'заголовок',
        'price'                 => 'ціна',
        'description'           => 'опис',
        'template_id'           => 'шаблон',
        'images'                => 'зображення',
    ],
];
