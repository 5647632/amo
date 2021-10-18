Работа скрипта amo-ats.php обычная, штатная, почти незаметна.
Скрипт запускается каждые 5,10 или 22 минуты, делет свою работу: читает АТС, получая данные за сегодня и вчера и анализирует их.
В частности, просмотр строк происходит так:
1) строки, у которых нет прицепа, то есть, последнего, со ссылкой, отбрасываются
2) строки, у которых есть ссылка, подвергаются обработке. Сначала ищем номер телефона в последних контактах, если её нет, ищем глубже, сотни на 2.
3) Контакты сразу имеют пару сотен записей, потом подчитываем каждый запуск, поэтому велика веротность того, что последний телефон будет найден.
4) ссылку в явном виде ищем в полях "контактов", там же находим идентификатор последней сделки
5) по идентификатору сделки вычитываем события и ищем ссылку в событиях. если находим, ничего не делаем, идём к следующему номеру телефона
6) не найденная ссылка записывается в АМО
7) все обработанные строки записываются в файл, следующий сеанс обработает только новые строки
8) возможны корректировки
