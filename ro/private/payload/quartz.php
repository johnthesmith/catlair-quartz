<?php
namespace catlair;



/*
    Inceptor payload
*/

require_once LIB . '/core/web_bot.php';



class Quartz extends Payload
{
    public const CONFIG = 'quartz.yaml';

    /*
        Main quartz loop

        section:
          url:
          request-timeout-mls:
          result-timeout-mcs:
            "*": 1e6

    */
    public function loop
    (
        /* Section id */
        string | null $task = null
    )
    {
        if
        (
            $this -> validate
            (
                empty( $task ),
                'quartz-task-is-empty',
                [ 'msg' => 'need --task=<task-id> arguent' ]
            ) -> isOk()
        )
        {
            /* Termination flag */
            $terminate = false;

            /* Mail loop */
            while( !$terminate )
            {
                $this -> setOk();
                $config = $this -> getConfig();

                /* Monitoring begin */
                $this -> monBegin();

                /* Строка вызова метода из монолита */
                $urlRequest = $config[ 'tasks' ][ $task ] ?? null;
                /* Таймаут ожидания ответа по URL */
                $requestTimeoutMls  = $config[ 'request-timeout-mls' ] ?? 0;

                /* Валидация входящих параметров */
                if
                (
                    $this
                    -> validate( empty( $urlRequest ), 'url-is-empty' )
                    -> isOk()
                )
                {
                    $url = Url::create() -> parse( $urlRequest );

                    /* Фиксация Момента начала запроса */
                    $this -> getMon( true )
                    -> set([ 'config', 'url' ], $url -> toString() )
                    -> set([ 'config', 'log' ], $this -> getLog() -> getFilePath() )
                    ;

                    /* Создание и исполнение запроса */
                    $answer = WebBot::create( $this -> getLog() )
                    -> setRequestTimeoutMls( $requestTimeoutMls )
                    -> setUrl( $url )
                    -> execute()
                    -> decodeJSON()
                    -> resultTo( $this )
                    -> getAnswer();
                }

                /* Обработка ответов сервера */
                if( $this -> isOk())
                {
                    $this -> setResultFromArray( $answer );
                }

                $this -> getMon( true )
                /* Отметка результата тика в мониторинге */
                -> add([ 'results', $this -> getCode() ])
                /* Отметка результата результата последнего */
                -> set([ 'last', 'result' ], $this -> getResultAsArray() );

                $timeout = $config[ 'result-timeout-mcs' ][ $this -> getCode() ] ??
                           ( $config[ 'result-timeout-mcs' ][ '*' ] ?? 1e6 );

                if( empty( $timeout ))
                {
                    $terminate = true;
                }
                else
                {
                    /* Idle */
                    usleep( $timeout );
                }

                /* Момент завершения запроса*/
                $this -> monEnd() -> flush( $this -> getMonPath());
            }
        }
        return $this;
    }
}
