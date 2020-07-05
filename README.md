# SeriesIndex
Typecho 开源博客的插件。可以在文章末尾自动添加一个【系列文章】列表，方便访客查看跟当前文章属于同系列的其他文章。

## 例子
http://blog.mangolovecarrot.net/2015/05/17/raspi-study06/

## 安装
- 下载后得到文件夹【SeriesIndex】
- 将这个文件夹上传到Typecho的usr/plugins/ 下
- 博客后台插件管理页面，启用SeriesIndex插件即可。

## 使用
1. Typecho后台管理-设置-永久链接-自定义文章路径，这里的设置不管是预定义的几种还是自定义的，要求至少要使用{slug}作为永久链接的一部分。
比如以下几种都是可以正常被插件识别的。
- wordpress风格 /archives/{slug}.html
- 按日期归档 /archives/{year}/{month}/{day}/{slug}.html
- 按分类归档 /{category}/{slug}.html
- 个性化定义 /{year}/{month}/{day}/{slug}/
而默认的下面这一种是不可以的（以后会考虑增加支持）
- 默认风格 /archives/{cid}/

2. 写系列文章的时候，需要你手动指定slug，格式为：系列前缀 + 数字编号。
比如你打算写一个关于python学习的系列文章，那么：
- 第1篇的slug指定为：pystudy01
- 第2篇的slug指定为：pystudy02
- 第3篇的slug指定为：pystudy03
...
要求只有两个：1.同系列的slug前缀必须一样 2.在前缀后面必须是数字（可以是任意位数，且同系列之间推荐使用相同位数的左补0数字，当然这不是必须的，但有可能影响列表的排序）
如果你想使用在你已经有的文章上也只需要简单的编辑一下你的那些文章，修改它们的slug就可以生效了。slug在Typecho的文章编辑页面是可以反复修改的。

## 原理
在显示文章内容的时候，插件会取得当前文章的slug，比如【pystudy02】，然后会去掉末尾的数字部分得到前缀【pystudy】。
然后在数据库中查找拥有相同前缀slug的文章最后一并输出。
