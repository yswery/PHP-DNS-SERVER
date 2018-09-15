# Event Subscriber

Server class supports registering event subscribers that can respond to various events emitted during
application life cycle. The Symfony Event Dispatcher component has been implemented to handle the events.

Following examples show how to implement and register such subscriber.

```php
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExampleEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
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
List of all possible events can be found in `yswery\DNS\Event\Events` abstract class.  
You need to create the EventDispatcher and add your subscriber classes or event methods.
The EventDispatcher is parsed to the Server constructor.

```php
$eventDispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
$eventDispatcher->addSubscriber(new ExampleEventSubscriber());
$server = new Server(new JsonResolver('./record.json'), $eventDispatcher);
```
## Supported events

* `Events::SERVER_START` - Server is started and listening for queries.
* `Events::SERVER_EXCEPTION` - Exception is thrown when processing and responding to query.
* `Events::MESSAGE` - Message is received from client in raw format.
* `Events::QUERY_RECEIVE` - Query is parsed to dns message class.
* `Events::QUERY_RESPONSE` - Message is resolved and sent to client.
