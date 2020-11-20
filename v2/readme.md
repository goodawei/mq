直接到队列queue模式

单task,多worker.  适合异步任务场景，多个worker监听一个队列，task通过轮训传递。支持ack,消息权重需要在梳理。

特点：task的投递只会被其中一个worker消费。