<?php

namespace App\Docs;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="Modnova API",
 *      description="API для системы лояльности Modnova. Предоставляет функционал для управления бонусной программой, включая начисление и списание бонусов, отслеживание уровня пользователя, историю операций и управление профилем пользователя.
 *      
 *      Технологии:
 *      - Laravel 11.x
 *      - PHP 8.3
 *      - MySQL
 *      - Laravel Sanctum для аутентификации
 *      - Firebase для push-уведомлений
 *      - Expo для мобильных уведомлений
 *      - Spatie Permission для управления ролями
 *      - Filament для админ-панели",
 *
 *      @OA\Contact(
 *          email="nfs2025@mail.ru"
 *      ),
 *
 *      @OA\License(
 *          name="Proprietary",
 *          url="https://modnova.ru"
 *      )
 * )
 *
 * @OA\Server(
 *      url="/api",
 *      description="API Server"
 * )
 * @OA\Server(
 *      url="/",
 *      description="Session Server"
 * )
 */
class ApiDocs {}
