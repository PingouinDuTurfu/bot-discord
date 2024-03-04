<html>
    <head>
        <style>
            body {
                width: 100%;
                height: 100%;
                margin: 0;
                padding: 0;
                background-color: black;
                color: #d1d5db;

            }

            .calendar {
                display: flex;
                flex-direction: row;
                height: 620px;
            }

            .day {
                width: 100%;
                margin: 5px;
                padding: 5px;
                position: relative;
                display: flex;
                flex-direction: column;
                align-items: center;
                background-color: #282c34;;
                border-radius: 10px;
                color:#f08d49;
            }

            .cours {
                position: absolute;
                top : 0;
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
                border-radius: 10px;
                width: 90%;
                color: #d1d5db;
            }
        </style>
    </head>
    <body>

<?php
    if(!isset($_GET["s"]) || !isset($_GET["e"]) || !isset($_GET["id"]))
        exit();

    $urlLogin = "https://services-web.cyu.fr/calendar/LdapLogin";
    $cookieFile = __DIR__ . "/cookie.txt";
    
    // Reset des cookies
    $fh = fopen($cookieFile, 'w' );
    fclose($fh);

    // Initialisation curl
    $ch = curl_init();

    //1ere requête : Récupérations des cookies (__RequestVerificationToken)
    curl_setopt($ch, CURLOPT_URL, $urlLogin);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.2 (KHTML, like Gecko) Chrome/22.0.1216.0 Safari/537.2' );

    // Désactivation des vérifs (je sais pas trop pourquoi mais sans ça casse)
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
    
    // Récupération des erreurs
//     curl_setopt($ch, CURLOPT_STDERR,$f = fopen("./error/exec1.txt", "w+"));

    // Récupération des cookies
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile); 
    
    // Exécution de la première requête
    $data=curl_exec($ch); 
    
    // Analyser la réponse et obtenir la valeur d'entrée cachée __RequestVerificationToken
    $regs=array();
    preg_match_all('/type="hidden" value="(.*)" /i', $data, $regs);
    $token = $regs[1][0];
    
    // Création des données à envoyer en POST
    $postData = array('__RequestVerificationToken'=>$token,
         'Name'=>getenv('MY_CY_ID'),
         'Password'=>getenv('MY_CY_PASSWORD'));

    // 2ème requête : Connection au site
    $urlSecuredPage = "https://services-web.cyu.fr/calendar/LdapLogin/Logon";
    curl_setopt($ch, CURLOPT_URL, $urlSecuredPage); 
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Définition de la requête en méthode POST
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    
    // Envoie des cookies de la 1ère requête
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    
    // Définition de l'origine
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Origin: https://services-web.cyu.fr'));

    // Définition de la reférence
    curl_setopt($ch, CURLOPT_REFERER, 'https://services-web.cyu.fr/calendar/LdapLogin');

    // Récupération des erreurs
//     curl_setopt($ch, CURLOPT_STDERR,$f = fopen("./error/exec2.txt", "w+"));

    // Mise à jour des cookies
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);

    // Exécution de la deuxième requête
    $data2 = curl_exec($ch);
    
    curl_setopt($ch, CURLOPT_URL, "https://services-web.cyu.fr/calendar/Home/GetCalendarData");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $headers = array(
        "authority: services-web.cyu.fr",
        "accept: application/json, text/javascript, */*; q=0.01",
        "x-requested-with: XMLHttpRequest",
        "sec-ch-ua-mobile: ?0",
        "user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.212 Safari/537.36",
        "content-type: application/x-www-form-urlencoded; charset=UTF-8",
        "origin: https://services-web.cyu.fr",
        "sec-fetch-site: same-origin",
        "sec-fetch-mode: cors",
        "sec-fetch-dest: empty",
        "referer: https://services-web.cyu.fr/calendar/cal?vt=agendaWeek&dt=2022-04-02&et=student&fid0=22014808",
        "accept-language: fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7",
    );
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

    $param = "start=".$_GET["s"]."&end=".$_GET["e"]."&resType=104&calView=agendaWeek&federationIds%5B%5D=".$_GET["id"]."&colourScheme=3";

    curl_setopt($ch, CURLOPT_POSTFIELDS, $param);

    $data3=curl_exec($ch);

    // Vérification si une erreur est survenue
    if(!curl_errno($ch))
    {
        // Affichage de la page
        $fp = fopen('./results.json', 'w');

        $ok = json_encode($data3);
        $ok = substr_replace($ok ,"",-1);
        $ok = substr($ok ,1);
        $ok = str_replace("\\", "", $ok);

        fwrite($fp, $ok);
        fclose($fp);
        
        $calendar = [];

        foreach (json_decode($ok) as $key => $value) {
            if(!array_key_exists(explode("T",$value->{"start"})[0], $calendar)) {
                $calendar[explode("T",$value->{"start"})[0]] = [];
            }

            $day = [];
            
            /* start */
            if(count(explode('T',$value->{'start'})) >= 2 )
                $day["start"] = substr_replace(explode('T',$value->{'start'})[1],"", -3);
            else
                $day["start"] = null;
            
            /* end */
            if(count(explode('T',$value->{'end'})) >= 2 )
                $day["end"] = substr_replace(explode('T',$value->{'end'})[1],"", -3);
            else
                $day["end"] = null;
            
            /* type */
            $day["type"] = $value->{'eventCategory'};

            /* Split description */
            $explode = explode('rnrn<br />rnrn', $value->{'description'});


            /* classroom */
	    if (count($explode) >= 4) {
                $isClassroom = preg_match('/E[0-9]{3}/', $explode[3], $classroom);
                if (!$isClassroom)
                    $isClassroom = preg_match('/A[0-9]{3}/', $explode[3], $classroom);
                if ($isClassroom)
                    $day["classroom"] = $classroom[0];
                else
                    $day["classroom"] = "null";
            } else 
                $day["classroom"] = "null";
            
            /* teacher */
            if (count($explode) >= 5)
                $day["teacher"] = str_replace("rn", "", str_replace("<br />", " - ", $explode[4]));
            else
                $day["teacher"] = "null";

            /* name */
            if (count($explode) >= 3)
                $day["name"] = $explode[2];
            else
                $day["name"] = "null";
            
            $calendar[explode("T", $value->{"start"})[0]][] = $day;
        }
                
        ksort($calendar);

        $string = "<div class='calendar'>";
        foreach ($calendar as $date => $day) {
            $string .= "<div class='day'>";
            $string .= toFrench(date('l', strtotime($date)))." ".explode("-",$date)[2];
            asort($day);
            foreach ($day as $key => $class) {
                if($value != null) {
                    $top = ((intval(explode(":",$class["start"])[0]) + intval(explode(":",$class["start"])[1])/60)-8)/10*500;
                    if($class["end"] != null) $height = ((intval(explode(":",$class["end"])[0]) + intval(explode(":",$class["end"])[1])/60)-8)/10*500 - $top;
                    else $height = 75;

                    switch ($class["type"]) {
                        case 'TD':
                            $color = "#56a65d";
                            break;

                        case 'CM':
                            $color = "#f66";
                            break;

                        case 'Examen':
                            $color = "purple";
                            break;

                        default:
                            $color = "#5865f2";
                    }
                }

                $string .= "<div class='cours' style='background-color:".$color.";top:".($top+30)."px;height:".$height."px;'>";

                $string .= "<div>";
                $string .= $class["start"]." - ".$class["end"];
                $string .= "</div>";
                $string .= "<div>";
                $string .= $class["type"]." ".$class["classroom"];
                $string .= "</div>";
                $string .= "<div>";
                $string .= $class["name"];
                $string .= "</div>";
                $string .= "<div>";
                $string .= $class["teacher"];
                $string .= "</div>";

                $string .= "</div>";
            }
            $string .= "</div>";
        }

        $string .= "</div>";

        echo $string;

    } else {
        echo 'Error';
    }

    // Fermeture de curl
    curl_close($ch);

    /* Traduit les jours en français */
    function toFrench($dy) {
        switch ($dy) {
            case 'Monday':
                return 'Lundi';
            
            case 'Tuesday':
                return 'Mardi';

            case 'Wednesday':
                return 'Mercredi';
            
            case 'Thursday':
                return 'Jeudi';

            case 'Friday':
                return 'Vendredi';

            case 'Saturday':
                return 'Samedi';

            case 'Sunday':
                return 'Dimanche';

            default:
                return $dy;
        }
    }
?>

    </body>
</html>


