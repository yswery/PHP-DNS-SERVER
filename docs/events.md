# Event Subscriber

Server class supports registering event subscribers that can respond to various events emitted during application
life cycle.

Following examples show how to implement and register such subscriber.

```php
class ExampleEventSubscriber implements EventSubscriberInterface
{
    public function getSubscribedEvents(): array
    {
        return [
            Event::SERVER_START => 'onServerStart',
            Event::MESSAGE => 'onMessage',
        ];
    }

    public function onServerStart(ServerStartEvent $event): void
    {
        // ToDo: implement method
    }

    public function onMessage(MessageEvent $event): void
    {
        // ToDo: implement method
    }
}
```
List of all possible events can be found in `yswery\DNS\Event` abstract class.  
Instance of subscriber class should be passed to server via register method.

```php
$server = new Server(new JsonResolver('./record.json'));
$server->registerEventSubscriber(new ExampleEventSubscriber());
```
##Supported events

* `Event::SERVER_START` - server started and listening for clients
* `Event::EXCEPTION` - exception is thrown in server class
* `Event::MESSAGE` - message is received from client in raw format
* `Event::QUERY_RECEIVE` - message is parsed to dns message class
* `Event::QUERY_RESPONSE` - message is resolved and sent to user
