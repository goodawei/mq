Publish/Subscribe

特点：一个task可以投递到多个worker.

流程： task 投递方式不指定队列queue了，而是通过指定exchange，将task投递给exchange，而exchange知道当前有多少worker在监听，如果一个都没有，那么task将抛弃。

worker 端将通过监听exchange方式，将绑定在exchange上的所有queue，全部取出消费。

通过： rabbitmqctl list_bindings 查看worker和task中exchange绑定信息。