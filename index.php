<?php

declare(strict_types=1);

use Oliver\{Application,Logger};
use Oliver\Reply\{Stocks,Orders,MarketOrderBuyStock,MarketOrderSellStock,Ping,ICanDo,Introduction,Repeat,Order};
use jamesRUS52\TinkoffInvest\{TIClient,TISiteEnum,TIException};
use Symfony\Component\DependencyInjection\{ContainerBuilder,Reference};

function main($event, $context): array
{
    require 'vendor/autoload.php'; // follow PSR-1

    // https://cloud.yandex.ru/docs/functions/lang/php/context
    $cloudRequestId = $context->getRequestId(); // @todo: error handling
    $token = $_ENV['TINKOFF_OPEN_API_EXCHANGE'] ?? ''; // @todo: $_SERVER

    $containerBuilder = new ContainerBuilder();
    $containerBuilder->setParameter('yandex.cloud.request.id', $cloudRequestId);
    $containerBuilder->setParameter('tinkoff.invest.token', $token);
    $containerBuilder->setParameter('tinkoff.invest.site', TISiteEnum::EXCHANGE);

    $containerBuilder
        ->register(Logger::class, Logger::class)
        ->addArgument('%yandex.cloud.request.id%')
    ;
    $containerBuilder
        ->register(TIClient::class, TIClient::class)
        ->addArgument('%tinkoff.invest.token%')
        ->addArgument('%tinkoff.invest.site%')
    ;
    $containerBuilder
        ->register(Application::class, Application::class)
        ->addMethodCall('setLogger', [new Reference(Logger::class)])
    ;

    $containerBuilder
        ->register(Stocks::class, Stocks::class)
        ->addArgument(new Reference(TIClient::class))
    ;
    $containerBuilder
        ->register(Orders::class, Orders::class)
        ->addArgument(new Reference(TIClient::class))
    ;
    $containerBuilder
        ->register(Order::class, Order::class)
        ->addArgument(new Reference(TIClient::class))
        ->addArgument(new Reference(Logger::class))
    ;
    $containerBuilder
        ->register(MarketOrderBuyStock::class, MarketOrderBuyStock::class)
        ->addArgument(new Reference(TIClient::class))
        ->addArgument(new Reference(Logger::class))
    ;
    $containerBuilder
        ->register(MarketOrderSellStock::class, MarketOrderSellStock::class)
        ->addArgument(new Reference(TIClient::class))
        ->addArgument(new Reference(Logger::class))
    ;

    $app = $containerBuilder->get(Application::class);
    $app->add(
        new Ping(),
        new ICanDo(),
        new Introduction(),
        new Repeat(),
        $containerBuilder->get(Stocks::class),
        $containerBuilder->get(Orders::class),
        $containerBuilder->get(Order::class),
        $containerBuilder->get(MarketOrderBuyStock::class),
        $containerBuilder->get(MarketOrderSellStock::class),
    );
    $app->setEvent($event);
    return $app->run();
}
