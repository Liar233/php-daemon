<?php

class Daemon
{
    protected $stop = false;

    protected $sleep = 1;

    //	В конструкторе мы задаём где демон будет хранить свой pid и с какой задержкой мы будем выполнять функцию несущую полезную нагрузку...
    //	Я решил установить значения по умолчанию, мало ли что...
    public function __construct($file = '/tmp/daemon.pid',$sleep=1)
    {
    	//	Проверяем не запущен ли наш демон...
        if ($this->isDaemonActive($file)) {
            echo "Daemon is already exsist!\n";
            exit(0);
        }

        $this->sleep = $sleep;

        //	Назначаем метод, который будет отвечать за обработку системных сигналов...
        pcntl_signal(SIGTERM,[$this,'signalHandler']);

        //	Получаем pid процесса с помощью встроенной функции getmypid() и записываем его в pid файл...
        file_put_contents($file, getmypid());
    }

    //	С помощью этого метода мы определяем работаем ли мы дальше или останавливаем процесс...
    public function run($func)
    {
    	//	Запускаем цикл, проверяющий состояние демона...
        while(!$this->stop){
            do{
            	//	Выполняем функцию несущую полезную нагрузку...
            	//	Получаем результат её работы...
                $resp = $func();

                //	Если результаты есть, то ждём установленный таймаут...
                if(!empty($resp)){
                    break;
                }
                //	Если результатов нет, то выполняем её повторно...
            }while(true);

            sleep($this->sleep);
        }
    }

    //	Метод занимается обработкой сигналов
    public function signalHandler($signo) {
        switch($signo) {
            case SIGTERM:
                //	При получении сигнала завершения работы устанавливаем флаг...
                $this->stop = true;
                break;
            //	default:
            //	Таким же образом записываем реакцию на любые другие сигналы если нам это нужно...
        }
    }

    //	Собственно детальная проверка что происходит с демоном, жив он или мёрт и как так получилось...
    public function isDaemonActive($pid_file) {
        if( is_file($pid_file) ) {
            $pid = file_get_contents($pid_file);
            //	Проверяем на наличие процесса...
            if(posix_kill($pid,0)) {
                //	Демон уже запущен...
                return true;
            } else {
                //	pid-файл есть, но процесса нет...
                if(!unlink($pid_file)) {
                    //	Не могу уничтожить pid-файл. ошибка...
                    exit(-1);
                }
            }
        }
        return false;
    }
}  
