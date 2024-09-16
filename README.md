# reactphp-x

## 安装

```
cp .env.example .env && composer install && php artisan reactphp:http start

```


### 验证并发

> 100个请求同时过来了，只处理前10个请求，让另外90个等待着去处理,第102个请求等待或丢弃掉(一个请求完成：header 和body 发送完毕)。
```
ab -n 102 -c 102 http://127.0.0.1:8000/concurrent
```

### 限制请求次数
> 一秒内100个请求过来了，只处理10个请求，其他90个请求发出429状态码或者等待1秒后在继续处理10个。当第102个请求过来时等待或丢弃掉 （一个请求完成：header 和body 发送完毕）。

```
ab -n 102 -c 102 http://127.0.0.1:8000/limiter
```

## License

MIT
