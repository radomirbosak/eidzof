<?php
  function getPlayerStatus($pname) {
    $q=MySQL_query("SELECT * FROM hra_hrac WHERE meno='".mysql_escape_string($pname)."';");
    if ($fot=mysql_fetch_array($q)) {
      //jetu taky hrac
      $q=MySQL_query("SELECT COUNT(*) FROM hra_teren WHERE hrac='".mysql_escape_string($fot['id'])."';");
      $pocet=MySQL_result($q,0);
      if ($pocet==0) {
        return('created');
      } else {
        return('playing');
      }
    } else {
      //neni v zozname
      $q=MySQL_query("SELECT COUNT(*) FROM hra_hrac;");
      $pocet=MySQL_result($q,0);
      if ($pocet>12) {
        return('full');
      } else {
        return('nothing');
      }
    }
  }
  function zaba_rozsir($x,$y) {
    echo "rozsirujem $x-$y ";
    if (($x>-1) and ($x<15) and ($y>-1) and ($y<15)) {
      echo " 1 ";
      if (rand(1,3)==1) {
        echo " 2 ";
        $zbq=MySQL_query("SELECT hrac FROM hra_teren WHERE x=$x AND y=$y;"); $hrac=MySQL_result($zbq,0);
        if ($hrac!=0) {
          echo " 3 ";
          $zbq=MySQL_query("SELECT COUNT(*) FROM hra_akcie WHERE x=$x AND y=$y AND typ='zaba';"); $hrac=MySQL_result($zbq,0);
          if ($hrac==0) {
            echo " NAKAZA $x-$y ";
            $zbq=MySQL_query("INSERT INTO hra_akcie (x,y,typ,hrac) VALUES ($x,$y,'zaba',0);");
          }
        }
        //$zb=MySQL_query("UPDATE hra_akcie")
      }
    }
    echo " koniec <br />";
  }
  function zaba_apply($x,$y) {
    echo " apply $x-$y ";
    if (($x>-1) and ($x<16) and ($y>-1) and ($y<16)) {
      if (rand(1,2)==1) {
        $zbq=MySQL_query("UPDATE hra_teren SET hrac=0 WHERE x=$x AND y=$y;");
        echo " SMRT $x-$y ";
      }
      $zbq=MySQL_query("DELETE FROM hra_akcie WHERE x=$x AND y=$y AND typ='zaba';");
    }
  }
  function verzus($a,$b) {
    return (lcg_value()*($a+$b)) < $a;
  }
  function oznam($hrac,$oznam) {
    $q=MySQL_query("INSERT INTO hra_oznamy (hrac,oznam) VALUES (".$hrac.",'".mysql_escape_string($oznam)."');");
  }
  function getwedrpole($wedr) {
    if     ($wedr=='jasno') {return array(1.0, 1.0, 1, 2, 3);}
    elseif ($wedr=='polo')  {return array(1.0, 1.5, 1, 2, 3);}
    elseif ($wedr=='blesk') {return array(1.0, 3.0, 1, 2, 3);}
    elseif ($wedr=='sneh')  {return array(0.5, 1.5, 1, 2, 3);}
    elseif ($wedr=='dazd')  {return array(1.0, 2.0, 2, 1, 3);}
    elseif ($wedr=='hmla')  {return array(1.0, 1.5, 1, 3, 2);}
  }
  function next_tah() {
    $q=MySQL_query("UPDATE hra_udaje SET cislo=(cislo+1) WHERE meno='stavhry';");
  
    $q=MySQL_query("SELECT stav FROM hra_wedr WHERE meno='aktualne';");
    $wedr=MySQL_result($q,0);
    $mojew=getwedrpole($wedr);
    
    $f_sused  = $mojew[0]; //1;
    $f_1      = $mojew[1]; //1.5;
    $f_obrana = $mojew[2]; //1;
    $f_utok   = $mojew[3]; //2;
    $f_stit   = $mojew[4]; //3;
    
    $poc=rand(1,6);
    if ($poc==1) {$wedr='jasno';}
    elseif ($poc==2) {$wedr='polo';}
    elseif ($poc==3) {$wedr='blesk';}
    elseif ($poc==4) {$wedr='sneh';}
    elseif ($poc==5) {$wedr='dazd';}
    elseif ($poc==6) {$wedr='hmla';}
    else {$wedr='jasno';}
    
    // zmarime zmenu pocasia - ma byt iba apokalipsa (fire)
    //$q=MySQL_query("UPDATE hra_wedr SET stav='".$wedr."' WHERE meno='aktualne';");
    
    
    
    //$q=MySQL_query("UPDATE hra_wedr SET stav='Už je noc a ty ešte furt hráš?' WHERE meno='noc'");
    
    //$q=MySQL_query("DELETE FROM hra_pm WHERE stav=1 AND komu!=-1");
    $q=MySQL_query("UPDATE hra_pm SET stav=2 WHERE stav=1 AND komu!=-1;");
    $q=MySQL_query("UPDATE hra_pm SET stav=1 WHERE stav=0;");
    
    // ZABA SA MNOZI
    $q=MySQL_query("SELECT * FROM hra_akcie WHERE typ='zaba';");
    while ($zaba=MySQL_fetch_array($q)) {
      $zaby[] = array($zaba['x'],$zaba['y']);
    }
    
    foreach ($zaby as $zaba) {
      zaba_rozsir($zaba[0]+1, $zaba[1]  );
      zaba_rozsir($zaba[0]-1, $zaba[1]  );
      zaba_rozsir($zaba[0]  , $zaba[1]+1);
      zaba_rozsir($zaba[0]  , $zaba[1]-1);
      
      zaba_apply( $zaba[0]  , $zaba[1]  );
    }
    
    
    $q=MySQL_query("DELETE FROM hra_oznamy;"); // zmazeme oznamy
    
    $q=MySQL_query("SELECT * FROM hra_akcie WHERE typ='doby' ORDER BY RAND();");
    while($doby=MySQL_fetch_array($q)) {
      $x=$doby['x']; $y=$doby['y']; $hracat=$doby['hrac'];
      $q2=MySQL_query("SELECT hrac FROM hra_teren WHERE x=".$x." AND y=".$y.";");
      $hracob= MySQL_result($q2,0); // obranca
      
      $q2=MySQL_query("SELECT COUNT(*) FROM hra_teren WHERE hrac=".$hracat." AND (ABS(x-".$x.")+ABS(y-".$y."))<2;");
      $susedov_at=MySQL_result($q2,0);
      
      $q2=MySQL_query("SELECT COUNT(*) FROM hra_teren WHERE hrac=".$hracob." AND (ABS(x-".$x.")+ABS(y-".$y."))<2;");  
      $susedov_ob=MySQL_result($q2,0);
      if ($hracob==0) {$susedov_ob=2;}
      
      $q2=MySQL_query("SELECT COUNT(*) FROM hra_akcie WHERE typ='obran' AND x=".$x." AND y=".$y.";");  
      $obstit=MySQL_result($q2,0);
      
      
      $utocnik = $f_sused*$susedov_at + $f_1 + $f_utok;
      $obranca = $f_sused*$susedov_ob + $f_1 + $f_obrana + $f_stit*$obstit;
      
      $q2=MySQL_query("SELECT COUNT(*) FROM hra_akcie WHERE typ='nehaj' AND x=".$x." AND y=".$y.";"); 
      if (MySQL_result($q2,0)==1) {
        $obranca=0;
        //delete prenechat
        $q2=MySQL_query("DELETE FROM hra_akcie WHERE typ='nehaj' AND x=".$x." AND y=".$y.";"); 
      }
      
      
      
      $q2=MySQL_query("SELECT meno FROM hra_hrac WHERE id=$hracat;");
      $meno_at=MySQL_result($q2,0);
      if ($hracob==0) {
        $meno_ob='Barbari';
      } else {
        $q2=MySQL_query("SELECT meno FROM hra_hrac WHERE id=$hracob;");
        $meno_ob=MySQL_result($q2,0);
      }
      
      //if ($meno_at=='vyr') {$utocnik = $utocnik + 2;}
      //if ($meno_ob=='vyr') {$obranca = $obranca + 2;}
      
      $imghrd='<img src="blog/ico_hrad.png" style="float: left; margin-right: 5px;" />';
      $imghrdl='<img src="blog/ico_hrad-sedy.png" style="float: left; margin-right: 5px;" />';
      $imgbtk='<img src="blog/ico_bitka.png" style="float: left; margin-right: 5px;" />';
      $imgbtkl='<img src="blog/ico_bitka-sedy.png" style="float: left; margin-right: 5px;" />';
      $brclb='<br style="clear: both" />';
      
      //arnendilova sprava pre kolo 150.
      $imggear='<img src="blog/ico_gear.png" style="float: left; margin-right: 5px;" />';
      oznam(15,"$imggear Tvoji vedci rozobrali hrdzavých golemov natretých načerveno a po dlhom bádaní úspešne objavili technológiu lodnej skrutky. Research complete. $brclb");
      
      if (verzus($utocnik,$obranca)) {
        $q2=MySQL_query("DELETE FROM hra_akcie WHERE x=$x AND y=$y AND hrac=$hracat;");
        $q2=MySQL_query("DELETE FROM hra_akcie WHERE x=$x AND y=$y AND hrac=$hracob;");
        
        $uz="[$x,$y]"; $koho=$kohop[$x.'-'.$y];
        // OZNAM DOBYL SI UZEMIE/DOBYLITI
        if ($dobyl[$x.'-'.$y]==1) {
          oznam($hracat,"$imgbtk Využil si pobojového zmätku a oslabenie vojsk po boji s hráčom $koho a lsťou si vzal uzemie $uz dobyvateľovi $meno_ob $brclb");
          oznam($hracob,"$imghrdl Hráč $meno_at využil oslabenie vojsk po boji a zradou ti vzal novonadobudnuté uzemie $uz $brclb");
        } elseif ($dobyl[$x.'-'.$y]==2) {
          oznam($hracat,"$imgbtk $meno_ob síce stihol odvrátiť útok hráča $koho, no územie $uz už nápor tvojích vojsk vydržať nedokázalo $brclb");
          oznam($hracob,"$imghrdl Ubránil si sa útoku hráča $koho, no keď už bol útok odrazený, $meno_at dokončil jeho prácu a zabral ti územie $uz $brclb");
        } else {
          if ($obranca==0) {
            oznam($hracat,"$imgbtk Hráč $meno_ob ti dobrovoľne podstúpil územie $uz $brclb");
            oznam($hracob,"$imghrdl Hráč $meno_at si vzal ponúkané územie $uz $brclb");
          } else {
            oznam($hracat,"$imgbtk Dobyl si územie $uz od hráča $meno_ob $brclb");
            oznam($hracob,"$imghrdl Hráč $meno_at ti dobyl územie $uz $brclb");
          }
          
        }
        $q2=MySQL_query("UPDATE hra_teren SET hrac=".$hracat." WHERE x=".$x." AND y=".$y." AND hrac=".$hracob.";");
        $dobyl[$x.'-'.$y]=1;
        $kohop[$x.'-'.$y]=$meno_ob;
      } else {
        $q2=MySQL_query("DELETE FROM hra_akcie WHERE x=$x AND y=$y AND hrac=$hracat;");
        $q2=MySQL_query("DELETE FROM hra_akcie WHERE x=$x AND y=$y AND hrac=$hracob;");
        $uz="[$x,$y]"; $koho=$kohop[$x.'-'.$y];
        // OZNAM OCHRANIL SI UZEMIE/NEDOBYL
        if ($dobyl[$x.'-'.$y]==1) {
          oznam($hracat,"$imgbtkl Hráč $meno_ob si svoje novo nadobudnuté územie $uz uchránil. $brclb");
          oznam($hracob,"$imghrd Prítomnosť armády zabránila hráčovi $meno_at aby nám vzal naše nové územie $uz $brclb");
        } elseif ($dobyl[$x.'-'.$y]==2) {
          oznam($hracat,"$imgbtkl Hráč $meno_ob zrejme stráži územie $uz ako oko v hlave. Dobyť sa ho nepodarilo hráčovi $koho, ubránilo sa i proti tebe. $brclb");
          oznam($hracob,"$imghrd Naše ťažko chránené územie $uz vydržalo nápor už druhého vojska - od hráča $meno_at $brclb");
        } else {
          oznam($hracat,"$imgbtkl Nepodarilo sa ti dobyť územie $uz od hráča $meno_ob $brclb");
          oznam($hracob,"$imghrd Podarilo sa ti ubrániť územie $uz pred hráčom $meno_at $brclb");
        }
        
        $dobyl[$x.'-'.$y]=2;
        $kohop[$x.'-'.$y]=$meno_at;
      }
    }
    
    $brclb='<br style="clear: both" />';
    $imgptp='<img src="blog/ico_potop.png" style="float: left; margin-right: 5px;" />';
    $q=MySQL_query("SELECT * FROM hra_akcie WHERE typ='potop';");
    while($pt=MySQL_fetch_array($q)) {
      //hrac typ x y
      $uz="[".$pt['x'].",".$pt['y']."]";
      $qp=MySQL_query("UPDATE hra_teren SET hrac=0, teren=0 WHERE x=".$pt['x']." AND y=".$pt['y'].";");
      $qhr=MySQL_query("SELECT * FROM hra_hrac;");
      while ($fhr=MySQL_fetch_array($qhr)) {
        oznam($fhr['id'],"$imgptp Voda sa zdvihla a pohltila územie $uz do svojich spárov $brclb");
      }
    }
    
    $q=MySQL_query("DELETE FROM hra_akcie WHERE typ<>'zaba';"); // zmazeme akcie
    
  }
  function _zaciatok() {}
  
  session_start();
  $action=$_GET['action'];
  $spoj=mysql_Connect('dbhost','username','password');
  Mysql_select_DB('dbname');
  
  $status = getPlayerStatus($_SESSION['logname']);
  
  $q=MySQL_query('SELECT COUNT(*) FROM hra_teren;');
  $pocet=MySQL_result($q,0);
  if ($pocet!=256) {$jehra=false;} else {$jehra=true;}
   //echo 'pocet=',$pocet;
  
  if ($jehra) {
    // ak je novy datum tak novy-tah
    $q=MySQL_query("SELECT datum FROM hra_datum;");
    $datumSQL=MySQL_result($q,0);
    $mojdatum=date("d.m.Y");
    if ($mojdatum!=$datumSQL) {
      $q=MySQL_query("UPDATE hra_datum SET datum='".mysql_escape_string($mojdatum)."';");
      next_tah();
    }
  }
  
  
  if ($action=='resetw') {
    function apply_wolrd_reset() {}
    /* --- RESET --- */
    if ($_SESSION['admin']!=1) {die;}
    $q=MySQL_query('DELETE FROM hra_teren;');
    
    for ($y=0;$y<16;$y++) {
      for ($x=0;$x<16;$x++) {
        $q=MySQL_query("INSERT INTO hra_teren (x,y,teren,hrac) VALUES (".$x.",".$y.",0,0);");
      }
    }
    $q=MySQL_query('DELETE FROM hra_hrac;');
    $q=MySQL_query('DELETE FROM hra_farby;');
    $q=MySQL_query("INSERT INTO hra_farby (id,farba,hrac) VALUES ('1','red','0');");
    $q=MySQL_query("INSERT INTO hra_farby (id,farba,hrac) VALUES ('2','green','0');");
    $q=MySQL_query("INSERT INTO hra_farby (id,farba,hrac) VALUES ('3','cyan','0');");
    $q=MySQL_query("INSERT INTO hra_farby (id,farba,hrac) VALUES ('4','magenta','0');");
    $q=MySQL_query("INSERT INTO hra_farby (id,farba,hrac) VALUES ('5','yellow','0');");
    $q=MySQL_query("INSERT INTO hra_farby (id,farba,hrac) VALUES ('6','white','0');");
    $q=MySQL_query("INSERT INTO hra_farby (id,farba,hrac) VALUES ('7','black','0');");
    $q=MySQL_query("INSERT INTO hra_farby (id,farba,hrac) VALUES ('8','orange','0');");
    $q=MySQL_query("INSERT INTO hra_farby (id,farba,hrac) VALUES ('9','lime','0');");
    $q=MySQL_query("INSERT INTO hra_farby (id,farba,hrac) VALUES ('10','blue','0');");
    $q=MySQL_query("INSERT INTO hra_farby (id,farba,hrac) VALUES ('11','silver','0');");
    $q=MySQL_query("INSERT INTO hra_farby (id,farba,hrac) VALUES ('12','purple','0');");
    $q=MySQL_query("INSERT INTO hra_farby (id,farba,hrac) VALUES ('13','brown','0');");
    
    Mysql_close($spoj);
    header('Location: http://zenit.fathamir.sk/hra');
    die;
  } elseif ($action=='preloginw') {
    function apply_prelogin() {}
    /* --- RESET --- */
    if ($_SESSION['admin']!=1) {die;}
    $_SESSION['logname']=$_GET['prelog'];
    header('Location: http://zenit.fathamir.sk/hra');
    die;
  } elseif ($action=='nextw') {
    function apply_wolrd_next() {}
    /* --- NEXT_TAH --- */
    if ($_SESSION['admin']!=1) {die;}
    
    next_tah();
    
    Mysql_close($spoj);
    header('Location: http://zenit.fathamir.sk/hra');
    die;
    
  } elseif ($action=='flipw') {
    function apply_flip_teren() {}
    /* --- FLIP --- */
    if ($_SESSION['admin']!=1) {die;}
    
    $x=$_GET['mx']; $y=$_GET['my'];
    $q=MySQL_query("UPDATE hra_teren SET teren=1-teren, hrac=0 WHERE x='".$x."' AND y='".$y."';");
    Mysql_close($spoj);
    header('Location: http://zenit.fathamir.sk/index.php?page=hra&action=edit');
    die;
    
  } elseif ($action=='narodw') {
    function apply_narod_create() {}
    /* --- CREATE NAROD --- */
    if ($status!='nothing') {die;}
    
    if ($_POST['farba']>13) {$_POST['farba']=13;}
    if ($_POST['farba']<1) {$_POST['farba']=1;}
    $q=MySQL_query("SELECT hrac FROM hra_farby WHERE id='".mysql_escape_string($_POST['farba'])."';");
    $hrac=mysql_result($q,0);
    if ($hrac!=0) {
      header('Location: http://zenit.fathamir.sk/hra');
      die; // chyba - farba uz je pouzita
    } else {
      
      $q=MySQL_query("SELECT COUNT(*) FROM hra_hrac");
      $hracov=MySQL_result($q,0);
      if ($hracov==0) {
        $maxid=0;
      } else {
        $q=MySQL_query("SELECT id FROM hra_hrac ORDER BY id DESC LIMIT 1;");
        $maxid=MySQL_result($q,0);
      }
      
      $q=MySQL_query("INSERT INTO hra_hrac (id,meno,farba,narod) VALUES ('".($maxid+1)."', '".
      mysql_escape_string($_SESSION['logname'])."', '".mysql_escape_string($_POST['farba'])."', '".mysql_escape_string($_POST['narod'])."');");
      
      $q=MySQL_query("UPDATE hra_farby SET hrac='".($maxid+1)."' WHERE id='".mysql_escape_string($_POST['farba'])."';");
    
      header('Location: http://zenit.fathamir.sk/hra');
      die; // juchu mame hraca
    } 
    
  } elseif ($action=='usadw') {
    function apply_narod_usad() {}
    /* --- USAD --- */
    if ($status!='created') {die;}
    
    $x=$_GET['mx']; $y=$_GET['my'];
    
    $q=MySQL_query("SELECT * FROM hra_teren WHERE x='".mysql_escape_string($x)."' AND y='".mysql_escape_string($y)."';");
    if ($fot=Mysql_fetch_array($q)) {
      if (($fot['teren']==1) and ($fot['hrac']==0)) {
        $q=MySQL_query("SELECT id FROM hra_hrac WHERE meno='".mysql_escape_string($_SESSION['logname'])."';");
        $mid=MySQL_result($q,0);
      
        $q=MySQL_query("UPDATE hra_teren SET hrac='".$mid."' WHERE x='".mysql_escape_string($x)."' AND y='".mysql_escape_string($y)."';");
      }
    }
    Mysql_close($spoj);
    header('Location: http://zenit.fathamir.sk/hra');
    die;
    
  } elseif ($action=='zmazw') {
    function apply_narod_zmaz() {}
    /* --- ZMAZ --- */
    $q=MySQL_query("SELECT id FROM hra_hrac WHERE meno='".mysql_escape_string($_SESSION['logname'])."';");
    $mhracid=MySQL_result($q,0);
    
    // treba zmazať dnesne-akcie, uzemia(teren), farby, oznamy, hrac
    $q=MySQL_query("DELETE FROM hra_akcie WHERE hrac=".$mhracid.";");
    $q=MySQL_query("UPDATE hra_teren SET hrac=0 WHERE hrac=".$mhracid.";");
    $q=MySQL_query("UPDATE hra_farby SET hrac=0 WHERE hrac=".$mhracid.";");
    $q=MySQL_query("DELETE FROM hra_oznamy WHERE hrac=".$mhracid.";");
    $q=MySQL_query("DELETE FROM hra_hrac WHERE id=".$mhracid.";");
    
    Mysql_close($spoj);
    header('Location: http://zenit.fathamir.sk/hra');
    die;
    
  } elseif ($action=='spravaw') {
    function apply_sprava_posli() {}
    /* --- POSLI SPRAVU --- */
    
    $q=MySQL_query("SELECT COUNT(*) FROM hra_cp WHERE reg='".mysql_escape_string($_SESSION['logname'])."';");
    $jetam=MySQL_result($q,0);
    
    if (($status!='playing') and ($status!='created') and ($_SESSION['admin']!=1) and ($jetam!=1) and ($_SESSION['logged']!=1)) {die;}
    
    $x=$_GET['mx']; $y=$_GET['my'];
    
    if ($_POST['sprava']=='') {die;}
    if (get_magic_quotes_gpc()) $_POST['sprava'] = stripslashes($_POST['sprava']);
    
    $q=MySQL_query("SELECT cislo FROM hra_udaje WHERE meno='stavhry';");
    $stavhry=MySQL_result($q,0);
    
    if ($_POST['hrac']==-2) {
      $q=MySQL_query("INSERT INTO hra_adminpm (ktomeno,sprava) VALUES ('".mysql_escape_string($_SESSION['logname'])."','($stavhry) ".mysql_escape_string($_POST['sprava'])."');");
      header('Location: http://zenit.fathamir.sk/hra-sprava');
      die;
    }
    
    $q=MySQL_query("SELECT id FROM hra_hrac WHERE meno='".mysql_escape_string($_SESSION['logname'])."';");
    if (mysql_num_rows($q)==0) {
      $hracid=-1;
    } else {
      $hracid=MySQL_result($q,0);
    }
    
    
    $mojemeno=$_SESSION['logname'];
    if ($jetam==1) {
      $q=MySQL_query("SELECT meno FROM hra_cp WHERE reg='".mysql_escape_string($_SESSION['logname'])."';");
      $mojemeno=MySQL_result($q,0);
    }
    
    if ($_POST['hrac']==-1) {
      $hracmeno='*';
    } elseif ($_POST['hrac']==-3) {
      $hracmeno=$_POST['cp'];
      $q=MySQL_query("SELECT COUNT(*) FROM hra_cp WHERE meno='".mysql_escape_string($hracmeno)."';");
      
      if (mysql_result($q,0)==0) {header('Location: http://zenit.fathamir.sk/hra-sprava'); die;}
    } else {
      if (($status=='created') and ($jetam!=1)) die('mŕtvi smú posielať správy hernému fóru, no hráčom nie.');
      $q=MySQL_query("SELECT meno FROM hra_hrac WHERE id=".mysql_escape_string($_POST['hrac']).";");
      $hracmeno=MySQL_result($q,0);
    }
    
    
    
    
    
    $q=MySQL_query("SELECT COUNT(*) FROM hra_pm WHERE ktomeno='".mysql_escape_string($_SESSION['logname'])."' AND komumeno=".mysql_escape_string($hracmeno)." AND stavhry=$stavhry;");
    $pocetsprav=MySQL_result($q,0);
    
    if (($pocetsprav<1) or ($_POST['hrac']==-1)) {
      $q=MySQL_query("INSERT INTO hra_pm (kto,komu,sprava,stav,ktomeno,komumeno,stavhry) VALUES (".
        $hracid.",".mysql_escape_string($_POST['hrac']).",'".mysql_escape_string($_POST['sprava'])."',0,'".
        mysql_escape_string($mojemeno)."','".mysql_escape_string($hracmeno)."',".$stavhry.");");
    }
    
    Mysql_close($spoj);
    header('Location: http://zenit.fathamir.sk/hra-sprava');
    die;
    
  } elseif ($action=='zrusspravuw') {
    function apply_sprava_zrus() {}
    /* --- ZRUS SPRAVU --- */
    
    $q=MySQL_query("SELECT cislo FROM hra_udaje WHERE meno='stavhry';");
    $stavhry=MySQL_result($q,0);
    
    $sprava=$_GET['sprava'];
    $q=MySQL_query("SELECT COUNT(*) FROM hra_pm WHERE id=$sprava AND ktomeno='".mysql_escape_string($_SESSION['logname'])."' AND stavhry=$stavhry;");
    
    if (mysql_result($q,0)==1) {
      $q=MySQL_query("DELETE FROM hra_pm WHERE id=$sprava;");
    }
        
    Mysql_close($spoj);
    header('Location: http://zenit.fathamir.sk/hra');
    die;
    
  } elseif ($action=='zaba') {
    function apply_zaba_rozmnoz() {}
    /* --- USAD --- */
    
    /*$q=MySQL_query("SELECT * FROM hra_pm;");
    while($pmka=MySQL_fetch_array($q)) {
      $h=MySQL_query("SELECT meno FROM hra_hrac WHERE id=".$pmka['kto'].";");
      $menoo=MySQL_result($h,0);
      if ($pmka['komu']==-1) {
        $menou='*';
      } else {
        $h=MySQL_query("SELECT meno FROM hra_hrac WHERE id=".$pmka['komu'].";");
        $menou=MySQL_result($h,0);
      }
      
      $h=MySQL_query("UPDATE hra_pm SET ktomeno='".mysql_escape_string($menoo)."', komumeno='".mysql_escape_string($menou)."', stavhry=(2-stav) WHERE id=".$pmka['id'].";");
    }*/
    $q=MySQL_query("SELECT * FROM hra_akcie WHERE typ='zaba';");
    while ($zaba=MySQL_fetch_array($q)) {
      $zaby[] = array($zaba['x'],$zaba['y']);
    }
    
    foreach ($zaby as $zaba) {
      zaba_rozsir($zaba[0]+1, $zaba[1]  );
      zaba_rozsir($zaba[0]-1, $zaba[1]  );
      zaba_rozsir($zaba[0]  , $zaba[1]+1);
      zaba_rozsir($zaba[0]  , $zaba[1]-1);
      
      zaba_apply( $zaba[0]  , $zaba[1]  );
    }
    
    //Mysql_close($spoj);
    //header('Location: http://zenit.fathamir.sk/hra');
    //die;
    
  } elseif ($action=='fixpm') {
    function apply_fixpm() {}
    /* --- USAD --- */
    $q=MySQL_query("SELECT cislo FROM hra_udaje WHERE meno='stavhry';");
    $stavhry=MySQL_result($q,0);
    
    $q=MySQL_query("SELECT * FROM hra_pm;");
    while($pmka=MySQL_fetch_array($q)) {
      $h=MySQL_query("SELECT meno FROM hra_hrac WHERE id=".$pmka['kto'].";");
      $menoo=MySQL_result($h,0);
      if ($pmka['komu']==-1) {
        $menou='*';
      } else {
        $h=MySQL_query("SELECT meno FROM hra_hrac WHERE id=".$pmka['komu'].";");
        $menou=MySQL_result($h,0);
      }
      
      $h=MySQL_query("UPDATE hra_pm SET ktomeno='".mysql_escape_string($menoo)."', komumeno='".mysql_escape_string($menou)."', stavhry=(2-stav) WHERE id=".$pmka['id'].";");
      $h=MySQL_query("UPDATE hra_pm SET stavhry=$stavhry WHERE stav=0 AND id=".$pmka['id'].";");
      $h=MySQL_query("UPDATE hra_pm SET stavhry=".($stavhry-1)." WHERE stav=1 AND id=".$pmka['id'].";");
    }
    
    
    Mysql_close($spoj);
    header('Location: http://zenit.fathamir.sk/hra');
    die;
    
  } elseif ($action=='dobyw') {
    function apply_pole_doby() {}
    /* --- DOBY --- */
    if ($status!='playing') {die;}
    
    $x=$_GET['mx']; $y=$_GET['my'];
    
    $q=MySQL_query("SELECT * FROM hra_hrac WHERE meno='".mysql_escape_string($_SESSION['logname'])."';");
    $ja=MySQL_fetch_array($q);
    $q=MySQL_query("SELECT * FROM hra_teren WHERE x='".mysql_escape_string($x)."' AND y='".mysql_escape_string($y)."';");
    $ter=MySQL_fetch_array($q);
    $qvedla=MySQL_query("SELECT COUNT(*) FROM hra_teren WHERE hrac='".mysql_escape_string($ja['id'])."' AND ((x='".($x-1)."' AND y='".($y)."') OR (x='".($x+1)."' AND y='".($y)."') OR (x='".($x)."' AND y='".($y-1)."') OR (x='".($x)."' AND y='".($y+1)."'));");  
    $pvedla=MySQL_result($qvedla,0); // pocet policok tvojich povedla
    
    $akcq=MySQL_query("SELECT COUNT(*) FROM hra_akcie WHERE hrac='".mysql_escape_string($ja['id'])."' AND typ='obran';");
    $obran=MySQL_result($akcq,0); // kolko krat obranujem
    $akcq=MySQL_query("SELECT COUNT(*) FROM hra_akcie WHERE hrac='".mysql_escape_string($ja['id'])."' AND typ='doby';");
    $doby=MySQL_result($akcq,0);  // kolko krat dobyjam
    $akcq=MySQL_query("SELECT typ FROM hra_akcie WHERE x='".mysql_escape_string($x)."' AND y='".mysql_escape_string($y)."' AND hrac='".mysql_escape_string($ja['id'])."';");
    if ($akcq) {
      $tuto=MySQL_result($akcq,0);
    } else {$tuto='';}
    
    // mame TER, VEDLA, PUTOK, POBR TUTO-AKCIA-MOJA
    if ($tuto=='obran') {
      // menim na podstup
      $akcq=MySQL_query("UPDATE hra_akcie SET typ='nehaj' WHERE x='".mysql_escape_string($x)."' AND y='".mysql_escape_string($y)."' AND hrac='".mysql_escape_string($ja['id'])."';");
    } elseif ($tuto!='') {
      // mazem svoju akciu
      $akcq=MySQL_query("DELETE FROM hra_akcie WHERE x='".mysql_escape_string($x)."' AND y='".mysql_escape_string($y)."' AND hrac='".mysql_escape_string($ja['id'])."';");
    } else {
      
      if ($ter['teren']==1) {
        //sme na susi
        
        if ($ter['hrac']==$ja['id']) {
          echo 'hrac=ja';
          if (($obran<2) and ($obran+$doby<3)) {
            //obranujem
            $q=MySQL_query("INSERT INTO hra_akcie(hrac,typ,x,y) VALUES ('".$ja['id']."','obran','".$x."','".$y."')");
          }
        } else {
          echo 'hrac=iny';
          echo 'doby='.$doby;
          if (($doby<2) and ($obran+$doby<3)) {
            
            if ($pvedla>0) {
              //dobyvam
              
              $q=MySQL_query("INSERT INTO hra_akcie(hrac,typ,x,y) VALUES ('".$ja['id']."','doby','".$x."','".$y."')");
            }
          }
        }
      }
    }
    
    Mysql_close($spoj);
    header('Location: http://zenit.fathamir.sk/hra');
    die;
  } elseif ($action=='hlasujw') {
    function apply_dielo_hlasuj() {}
    /* --- HLASUJ --- */
    
    $dielo=$_GET['hlas'];
    
    $q=MySQL_query("SELECT COUNT(*) FROM hra_galeria_hlasy WHERE reg='".mysql_escape_string($_SESSION['logname'])."';");
    $pocet=MySQL_result($q,0);
          
    $q=MySQL_query("SELECT COUNT(*) FROM hra_galeria_hlasy WHERE galid=$dielo AND reg='".mysql_escape_string($_SESSION['logname'])."';");
    $uzsom=MySQL_result($q,0);
    
    if (($pocet<2) and ($uzsom==0)) {
      $q=MySQL_query("INSERT INTO hra_galeria_hlasy (reg, galid) VALUES ('".mysql_escape_string($_SESSION['logname'])."',$dielo);");
    }
    
    Mysql_close($spoj);
    header('Location: http://zenit.fathamir.sk/hra-galeria');
    die;
  }
  function menu_panel() {}

  if ($_SESSION['admin']==1) {
    echo '<div id="uppermenu">';
    if ($jehra) {
      echo '<div class="uppermenuitem"><a href="hra"><img src="blog/ico_sword.png" /><span class="textup10">Hra</span></a></div>';
      echo '<div class="uppermenuitem"><a href="hra-pomoc"><img src="blog/ico_quest.png" /><span class="textup10">Pomoc</span></a></div>';
      echo '<div class="uppermenuitem"><a href="hra-sprava"><img src="blog/ico_chat.png" /><span class="textup10">Správy</span></a></div>';
      echo '<div class="uppermenuitem"><a href="hra-stats"><img src="blog/ico_pie.png" /><span class="textup10">Štatistiky</span></a></div>';
      echo '<div class="uppermenuitem"><a href="hra-galeria"><img src="blog/ico_gem.png" /><span class="textup10">Galéria</span></a></div>';
      echo '<div class="uppermenuitem"><a href="hra-cinter"><img src="blog/ico_hrob.png" /><span class="textup10">Cintorín</span></a></div>';
      echo '<div class="uppermenuitem"><a href="index.php?page=hra&amp;action=edit"><img src="blog/ico_draw.png" /><span class="textup10">Editovať terén</span></a></div>';
      echo '<div class="uppermenuitem"><a href="hra.php?action=nextw"><img src="blog/ico_next.png" /><span class="textup10">Ďalší ťah</span></a></div>';
    } else {
      echo '<div class="uppermenuitem"><img src="blog/ico_lock.png" /><span class="textup10">Hra nie je nainicializovaná</span></div>';
    }
    
    echo '<div class="uppermenuitem"><a href="index.php?page=hra&amp;action=reset"><img src="blog/ico_delete.png" /><span class="textup10">Resetovať hru</span></a></div>
    </div>';
  
  } else {
    echo '<div id="uppermenu">';
    if ($jehra) {
      echo '<div class="uppermenuitem"><a href="hra"><img src="blog/ico_sword.png" /><span class="textup10">Hra</span></a></div>';
      echo '<div class="uppermenuitem"><a href="hra-pomoc"><img src="blog/ico_quest.png" /><span class="textup10">Pomoc</span></a></div>';
      if ($_SESSION['logged']==1) echo '<div class="uppermenuitem"><a href="hra-sprava"><img src="blog/ico_chat.png" /><span class="textup10">Správy</span></a></div>';
      echo '<div class="uppermenuitem"><a href="hra-stats"><img src="blog/ico_pie.png" /><span class="textup10">Štatistiky</span></a></div>';
      echo '<div class="uppermenuitem"><a href="hra-galeria"><img src="blog/ico_gem.png" /><span class="textup10">Galéria</span></a></div>';
      echo '<div class="uppermenuitem"><a href="hra-cinter"><img src="blog/ico_hrob.png" /><span class="textup10">Cintorín</span></a></div>';
    } else {
      echo '<div class="uppermenuitem"><img src="blog/ico_lock.png" /><span class="textup10">Hra nie je nainicializovaná</span></div>';
    }
    echo '</div>';
  
  }
  
  if ($action=='reset') {
    function menu_reset() {}
    if ($_SESSION['admin']!=1) {echo 'access denied'; die;}
    echo '<h1 class="title">Hra :: reset</h1>'."\n";
    echo '<a href="hra.php?action=resetw"><img src="http://scienceblogs.com/gregladen/orly.jpeg" width="64" alt="orly?" /></a>
    <br />Warning: this will destroy all mankind';
    
  } elseif ($action=='edit') {
    function menu_edit_teren() {}
    if ($_SESSION['admin']!=1) {echo 'access denied'; die;}
    echo '<h1 class="title">Hra :: edit</h1>'."\n";
    
    $q=MySQL_query('SELECT * FROM hra_teren ORDER BY y ASC, x ASC;');
    
    echo '<div id="hra">';
    for ($y=0;$y<16;$y++) {
      echo '<div class="riadok" id="riadok'.$y.'">';
      for ($x=0;$x<16;$x++) {
        
        $fot=MySQL_fetch_array($q);
        if ($fot['teren']==0) {
          $beklas='teren-voda';
        } else {
          if ($fot['hrac']==0) {
            $beklas='teren-sucho';
          } else {
            $farq=MySQL_query("SELECT farba FROM hra_hrac WHERE id=".mysql_escape_string($fot['hrac']).';' );
            $far=MySQL_result($farq,0);
            $beklas='hrac-'.$far;
          }
        }
        echo '<div class="hra-'.$beklas.'" id="bunka'.$x.'-'.$y.'"><a href="hra.php?action=flipw&amp;mx='.$x.'&amp;my='.$y.'">&nbsp;</a></div>';
      }
      echo '</div>';
    }
    echo '</div>';
    
  } elseif ($action=='narod') {
    function menu_narod_vytvor() {}
    if (($status=='nothing') and ($_SESSION['logged']==1)) {
      echo '<form action="hra.php?action=narodw" method="post">
        Meno národa: <input type="text" size="32" name="narod" /><br />
        Farba: <select name="farba">';
      $q=MySQL_query("SELECT * FROM hra_farby WHERE hrac=0;");
      while ($fot=MySQL_fetch_array($q)) {
        echo '<option value="'.$fot['id'].'">'.$fot['farba'].'</option>';
      }
      echo '</select><br /><input type="submit" value="Pridaj">';
      echo '</form>';
    } else {
      echo 'Buď už hráš, alebo niesi prihlásený. V každom prípade nový národ stvoriť nemôžeš. :(';
    }
    
  } elseif ($action=='zmaz') {
    function menu_narod_zmaz() {}
    if ((($status=='playing') or ($status=='created')) and ($_SESSION['logged']==1)) {
      echo 'ozaj chceš zmazať svoje konto v hre? <a href="hra.php?action=zmazw">áno</a>';
    } else {
      echo 'Čo chceš mazať?';
    }
    
  } elseif ($action=='pomoc') {
    function menu_pomoc() {}
    //echo ''."\n";
    ?>
    
    <h1 class="title">Pravidlá</h1>
    <strong>O hre:</strong> Každý hráč môže v jednom kole útočiť <img src="blog/ico_sword.png" alt="meč" /> na max. 2 cudzie susediace políčka,
    takisto môže brániť <img src="blog/ico_shield.png" alt="štít" /> max. 2 svoje políčka. Spolu však môže použiť najviac 3 akcie útok/obrana.<br />
    Jednému kolu zodpovedá jeden reálny deň.<br />
    O polnoci prebieha prepočet, ktoré vyhodnotí akcie jednotlivých hráčov daného kola.<br />
    Hrať môžu len hráči prihlásení. (zaregistrovať sa zatiaľ nedá, posťažuj sa na <a href="http://zenit.fathamir.sk/forum">fóre</a>)<br />
    <br />
    <h1 class="title" title="Často Kladené Otázky">ČKO</h1>
    <strong>Kedy bude hra hotová?</strong><br />
    V nedeľu 20.9.2009 o 0:00.<br /><br />
    <strong>Kedy sa hra skončí?</strong><br />
    V nedeľu 21.2.2010 o 0:00.<br /><br />
    <strong>Ako sa hrá?</strong><br />
    Kliknutím na svoje alebo nepriateľské susediace políčko môže hráč dané pole obraňovať alebo naň útočiť.<br /><br />
    <strong>Ako funguje bojovanie? Podľa čoho niekedy bitku vyhrám, inokedy nie?</strong><br />
    Bojuje sa na každom poli ktoré bolo nejakým hráčom označené mečom, útoku sa bráni hráč, ktorý dané pole práve vlastní.
    Úspech boja závisí od viacerých faktorov. Samotný vzorec boja je však hráčom neprístupný,
    aby bola hra viac tajomná a aby daný vzorec hráčov neodsudzoval robiť jasne optimálne ťahy.<br /><br />
    <strong>Možeš hru nejako vylepšiť?</strong><br />
    <s>Plánuje sa pridať zobrazenie starších správ.</s>
    Nové návrhy sú vítané na <a href="http://zenit.fathamir.sk/hra-sprava">hernom fóre</a>,
    prípadne na <a href="http://zenit.fathamir.sk/forum">verejnom fóre</a>.<br /><br />
    <strong>Ako sa hra volá?</strong><br />
    Eidžof.<br /><br />
    <strong>Ako môžem niekomu bez boja prenechať územie?</strong><br />
    Na územie bez akcie treba kliknúť dva krát, až na ňom bude obrázok šipky. Ak na toto územie v najbližom kole bude nejaký hráč útočiť, dobyje ho bez boja.<br /><br />
    <strong>Aký veľký je zdrojový kód?</strong><br />
    V súčasnosti má <?php
      $sz=filesize('hra.php');
      if (strlen($sz)<4) echo $sz; 
      else {
        echo substr($sz,0,-3),' ',substr($sz,-3);
      } 
       
    ?> Bytov<br /><br />
    <?php
  } elseif ($action=='sprava') {
    function menu_sprava() {}
    
    $q=MySQL_query("SELECT COUNT(*) FROM hra_cp WHERE reg='".mysql_escape_string($_SESSION['logname'])."';");
    $jetam=MySQL_result($q,0);

    if ((($status=='playing') or ($status=='created') or ($_SESSION['admin']==1) or ($jetam==1)) or/*and*/ ($_SESSION['logged']==1)) {
      echo '<form action="hra.php?action=spravaw" method="post">
        Správa: <input type="text" size="64" name="sprava" /><br />
        Hráč: <select name="hrac" id="selhrac" onchange="var moje=document.getElementById(\'cpspan\'); if (document.getElementById(\'selhrac\').value==-3) moje.style.display=\'inline\'; else moje.style.display=\'none\'; ">';
      echo '<option value="-1">Herné fórum</option>';
      echo '<option value="-2">Správa adminovi</option>';
      //echo '<option value="-3">Cizí postava</option>';
      //echo '<option value="-1">------------</option>';
      if (($status=='created') and !($_SESSION['admin']==1)) {
        
      } else {
        $q=MySQL_query("SELECT * FROM hra_hrac;");
        while ($fot=MySQL_fetch_array($q)) {
          //if ($fot['meno']!=$_SESSION['logname']) echo '<option value="'.$fot['id'].'">'.$fot['meno'].'</option>';
        }
      }
      
      
      echo '</select>&nbsp;&nbsp;&nbsp;<span id="cpspan" style="display: none;">CP:<input type="text" size="32" name="cp" /></span><br /><input type="submit" value="Pošli">';
      echo '</form>';
      echo '<a href="hra-starsie">Zobraziť staršie správy</a><br />';
      //echo 'Počas jedného dňa môžeš poslať maximálne jednu súkromnú správu každému hráčovi. Do herného fóra ľubovoľný počet. Správy, ktoré posielate ako postava v hre(t.j. nie hráč) a sú adresované iba jedinému človeku/hráčovi posielajte prosím cez súkromné správy a nie sem.';
      echo 'Hra sa skončila. Správy do herného fóra už môžu vidieť a posielať všetci s kontom na zenit.fathamir.sk alebo fathamir.sk.';
      echo '<hr />';
      $q=MySQL_query("SELECT * FROM hra_pm WHERE komumeno='*' ORDER BY id DESC;");
      while ($fot=MySQL_fetch_array($q)) {
        //if ($fot['meno']!=$_SESSION['logname']) echo '<option value="'.$fot['id'].'">'.$fot['meno'].'</option>';
        //$q2=MySQL_query("SELECT meno FROM hra_hrac WHERE id=".$fot['kto'].";");
        echo '<div><b>'.htmlspecialchars($fot['ktomeno']).'</b>: '.htmlspecialchars($fot['sprava']).'</div>';
      }
    } else {
      echo 'Z nejakého dôvodu nemôžeš posielať správy. :(';
    }
  } elseif ($action=='oldsprava') {
    function menu_sprava_stare() {}
    
    $q=MySQL_query("SELECT COUNT(*) FROM hra_cp WHERE reg='".mysql_escape_string($_SESSION['logname'])."';");
    $jetam=MySQL_result($q,0);
    
    if ((($status=='playing') or ($status=='created') or ($jetam==1)) and ($_SESSION['logged']==1)) {
      $q=MySQL_query("SELECT cislo FROM hra_udaje WHERE meno='stavhry';");
      $stavhry=MySQL_result($q,0);
      
      if ($jetam==1) {
        $q=MySQL_query("SELECT meno FROM hra_cp WHERE reg='".mysql_escape_string($_SESSION['logname'])."';");
        $mojemeno=mysql_result($q,0);
      } else {
        $mojemeno=$_SESSION['logname'];
      }
      
      $qp=MySQL_query("SELECT * FROM hra_pm WHERE komumeno='".mysql_escape_string($mojemeno)."' AND stavhry<$stavhry ORDER BY id DESC;");
      $spravy='';
      while($spr=MySQL_fetch_array($qp)) {
        $kt=MySQL_query("SELECT narod FROM hra_hrac WHERE meno='".mysql_escape_string($spr['ktomeno'])."';");
        if ($m=MySQL_result($kt,0)) $ktomi=$m; else $ktomi='<s>'.$spr['ktomeno'].'</s>';
      
        $spravy.='<div>';
        $spravy.='<img src="blog/ico_chat.png" style="float: left; margin-right: 5px;" alt="správa" />';
        //$kt=MySQL_query("SELECT meno FROM hra_hrac WHERE id=".$spr['kto'].";");
        $spravy.='['.$spr['stavhry'].'] <strong>'.$ktomi.'</strong>: '.htmlspecialchars($spr['sprava']) ;
        $spravy.='<br style="clear: both;" /></div>';
      }
      //if ($spravy!='') {$spravy='<hr />'.$spravy;}
      
      $qp=MySQL_query("SELECT * FROM hra_pm WHERE ktomeno='".mysql_escape_string($mojemeno)."' ORDER BY id DESC;");
      $spravy2='';
      while($spr=MySQL_fetch_array($qp)) {
        $kt=MySQL_query("SELECT narod FROM hra_hrac WHERE meno='".mysql_escape_string($spr['komumeno'])."';");
        
        
        $spravy2.='<div>';
        $spravy2.='<img src="blog/ico_archiv.png" style="float: left; margin-right: 5px;" alt="správa" />';
        
        if ($spr['komumeno']=='*') {$komusom='Herné fórum';}
        elseif ($m=MySQL_result($kt,0)) $komusom=$m;
        else { $komusom='<s>'.$spr['komumeno'].'</s>';}
        
        $spravy2.='['.$spr['stavhry'].'] Správa pre <strong>'.$komusom.'</strong>: '.htmlspecialchars($spr['sprava']) ;
        $spravy2.='<br style="clear: both;" /></div>';
      }
      //if ($spravy2!='') {$spravy2='<hr />'.$spravy2;}
      echo 'Práve prebieha kolo: ',$stavhry,'<hr />';
      echo '<table><tr><td style="vertical-align: top;" width="50%">'.$spravy.'</td><td style="vertical-align: top;">'.$spravy2.'</td></tr></table>';
    } else {
      echo 'Z nejakého dôvodu nemôžeš prezerať staré správy. :(';
    }
    
  } elseif ($action=='stats') {
    function menu_stats() {}
    echo '<div style="font-size: 12px;">';
    echo '<strong>Največia ríša</strong>:<br /><br />';
    $max=0; $l=0;
    $q=MySQL_query("SELECT *,COUNT(hrac) FROM hra_teren GROUP BY hrac HAVING hrac>0 ORDER BY COUNT(hrac) DESC;");
    while ($hr=MySQL_fetch_array($q)) {
      if ($max<$hr['COUNT(hrac)']) {$max=$hr['COUNT(hrac)'];}
      if (($max==$hr['COUNT(hrac)'])or ($l<3)) {
        $hracq=MySQL_query("SELECT * FROM hra_hrac WHERE id=".$hr['hrac'].";");
        $hr2=MySQL_fetch_array($hracq);
        
        $beklas='hrac-'.$hr2['farba'];
        $zoznam= '<div';
        //if ($hr['meno']==$_SESSION['logname'])  {$zoznam.= ' style="border: 5px solid orange;"';}
        //else                                    {$zoznam.= ' style="border: 5px solid white;"';}
        $zoznam.= '><div style="float: left; width: 32px; height:32px; border: 1px solid black; margin-right: 5px;" class="hra-'.$beklas.'"></div>';
        $zoznam.= 'národ: <strong>'.$hr2['narod'].'</strong><br />';
        $zoznam.= 'počet provincii: <strong>'.$hr['COUNT(hrac)'].'</strong>';
        $zoznam.= '<br style="clear: both;" /></div>';
        echo $zoznam;
        $l++;
      }
      
    } 
    $q=MySQL_query("SELECT cislo FROM hra_udaje WHERE meno='stavhry';");
    $stavhry=MySQL_result($q,0);
      
    echo '<br /><br /><strong>Najsilnejšia diplomacia:</strong>:<br /><br />';
    $max=0; $l=0;
    $q=MySQL_query("CREATE OR REPLACE VIEW laso AS
SELECT *
FROM hra_pm
WHERE stavhry<$stavhry AND stavhry>".($stavhry-10).";");
    $q=MySQL_query("SELECT *,COUNT(komumeno) FROM laso GROUP BY komumeno ORDER BY COUNT(komumeno) DESC;");
    while ($hr=MySQL_fetch_array($q)) {
      if ($max<$hr['COUNT(ktomeno)']) {$max=$hr['COUNT(komumeno)'];}
      $hracq=MySQL_query("SELECT COUNT(*) FROM hra_hrac WHERE meno='".mysql_escape_string($hr['komumeno'])."';");
      $pocet=MySQL_result($hracq,0);
      if ((($max==$hr['COUNT(komumeno)'])or ($l<3)) and $pocet==1) {
        $hracq=MySQL_query("SELECT * FROM hra_hrac WHERE meno='".mysql_escape_string($hr['komumeno'])."';");
        $hr2=MySQL_fetch_array($hracq);
        
        $beklas='hrac-'.$hr2['farba'];
        $zoznam= '<div';
        //if ($hr['meno']==$_SESSION['logname'])  {$zoznam.= ' style="border: 5px solid orange;"';}
        //else                                    {$zoznam.= ' style="border: 5px solid white;"';}
        $zoznam.= '><div style="float: left; width: 32px; height:32px; border: 1px solid black; margin-right: 5px;" class="hra-'.$beklas.'"></div>';
        $zoznam.= 'národ: <strong>'.$hr2['narod'].'</strong><br />';
        $zoznam.= 'počet diplomatických správ: <strong>'.$hr['COUNT(komumeno)'].'</strong>';
        $zoznam.= '<br style="clear: both;" /></div>';
        echo $zoznam;
        $l++;
      }
      
    }
    
    echo '<br /><br /><strong>Vyťaženosť poštových holubov:</strong>:<br /><br />';
    $max=0; $l=0;
    $q=MySQL_query("SELECT stavhry,COUNT(stavhry) FROM hra_pm WHERE stavhry<$stavhry GROUP BY stavhry ORDER BY stavhry DESC LIMIT 5;");
    //$q=MySQL_query("SELECT *,COUNT(komumeno) FROM laso GROUP BY komumeno ORDER BY COUNT(komumeno) DESC;");
    while ($hr=MySQL_fetch_array($q)) {
      echo 'kolo: <strong>',$hr['stavhry'],'</strong>; vyťaženosť: ',$hr['COUNT(stavhry)'],'%<br />';
      
      
    } 
     
    echo '</div>';
  } elseif ($action=='galeria') {
    function menu_galeria() {}
    echo '<h1 class="title">Galéria:</h1>';
    echo '';
    if (/*$_SESSION['admin']!=1*/ false) {echo 'pracuje sa na tom; almost done;';} else {
      if (IsSet($_GET['dielo'])) {
        function zobrazdielo($subor,$nazov,$autor,$koment,$id) {
          echo '<table class="hra-galerydetail"><tr>';
          echo '<td width="40%"><a href="diela/',$subor,'"><img src="diela/',$subor,'" style="border: 1px solid black; max-width:500px;" alt="'.htmlspecialchars($nazov).'" /></a></td><td valign="top">';
          echo '<h2>',$nazov,'</h2>';
          echo '<strong>autor:</strong> ',$autor,'<br />';
          echo '<strong>komentár:</strong> ',$koment;
          $q=MySQL_query("SELECT COUNT(*) FROM hra_galeria_hlasy WHERE reg='".mysql_escape_string($_SESSION['logname'])."';");
          $pocet=MySQL_result($q,0);
          
          $q=MySQL_query("SELECT COUNT(*) FROM hra_galeria_hlasy WHERE galid=$id AND reg='".mysql_escape_string($_SESSION['logname'])."';");
          $uzsom=MySQL_result($q,0);
          
          if ($uzsom==1) echo '<br /><br />Za toto dielo si už hlasoval';
          elseif (($autor=='zenitova_sestra') or ($autor=='Zenit')) echo '';
          elseif ($_SESSION['logged']==0) echo '';
          elseif ($pocet<2) 
            echo '<br /><br /><a href="hra.php?action=hlasujw&amp;hlas='.$id.'">hlasuj za toto dielo</a> (máš ešte '.(2-$pocet).' hlasy)';
          else echo '<br /><br />už si hlasoval 2x';
          echo '</td></tr></table>';
        }
        $dielo=$_GET['dielo'];
        if ($dielo==1) zobrazdielo('zenitgame_imperator.png',
          'Imperátor', 'vyr',
          'Jedna sa o dobovu malbu z ruky slavneho umelca, ktora zobrazuje Jeho Magnificeniciu, Igora II, Vyra Velkeho, ArciVelMajstra Rudeho Radu',
          1);
        if ($dielo==2) zobrazdielo('aelfica.png',
          "Y'wanna Aelf'ica Bielovlasá", 'Jenx',
          "Portrét Y'wanny Aelf'ice Bielovlasej (jej výsosť odmietala pózovať dlho, vyhovárajúc sa na akúsi vojnu), panovníčky snehoelfskej",
          2);
        if ($dielo==3) zobrazdielo('mapa.gif',
          "Kartografická kolekcia máp Eidžofu", 'Lord Tarmin',
          "Výtvor má podobu kartografickej kolekcie máp Eidžofu v priebehu dejín zostavenej trolelfími kresličmi a kartografmi. Obsahuje súvislý cyklus trinástich máp, so strategickými manévrami trolelfej armády a s vyznačeným hlavným mestom veľríše trolelfov, odkiaľ sa raz bude vládnuť celému svetu - mesto nesie hrdý názov Eidžof-Trolelf, v prastarom vznešenom jazyku trolelfov Age of Trollelves.",
          3);
        if ($dielo==4) zobrazdielo('zenit.png',
          "Zenit, posledný škriatkovský magič", 'zenitova_sestra',
          "Zenit bol mocným magičom veľkého škriatkovského národa, no objavila sa hrozná žabacia chrípka a zabila väčšinu škriatkov. Zenit ostal ich posledným magičom.",
          4);
        if ($dielo==5) zobrazdielo('nationmap.png',
          "Národná mapa", 'Felagund',
          "".'<br /><br />Prvé miesto <img src="blog/ico_pohar-unua.png" style="border: 0px;" />',
          5);
        if ($dielo==6) zobrazdielo('edzof.png',
          "Edžof", 'Sasoryba',
          "",
          6);
        if ($dielo==7) zobrazdielo('eidzof-basen.png',
          "Eidžof", 'Sasoryba',
          "".'<br /><br />Druhé miesto <img src="blog/ico_pohar-dua.png" style="border: 0px;" />',
          7);
        if ($dielo==8) zobrazdielo('eidzofskr.png',
          "Žabia hrozba", 'Zenit',
          "Žabacia chrípka je hrozivá nemoc, ktorá vyhubila takmer celý škriatkovský národ.",
          8);
        
      } else {
        function pisdielo($subor,$autor,$nr,$styl) {
          echo '<div class="hra-galerydiv"><div><a href="hra-galeria-dielo-'.$nr.'"><img src="diela/'.$subor.'"'.$styl.' /></a><span>autor: '.$autor.'</span></div></div>'; }
        pisdielo('zenitgame_imperator-thumb.png','vyr',             1);
        pisdielo('aelfica.png',                  'Jenx',            2);
        pisdielo('mapa.gif',                     'Lord Tarmin',     3,' style="width: 200px;"');
        pisdielo('zenit-thumb.png',              'zenitova_sestra', 4);
        pisdielo('nationmap-thumb.png',          'Felagund <img src="blog/ico_pohar-unua.png" style="border: 0px;" />',        5);
        pisdielo('edzof-thumb.png',              'Sasoryba',        6);
        pisdielo('eidzof-basen-thumb.png',       'Sasoryba <img src="blog/ico_pohar-dua.png" style="border: 0px;" />',        7);
        pisdielo('eidzofskr-thumb.png',          'Zenit',           8);
      }
      
    }
  } elseif ($action=='cinter') {
    function menu_cinter() {}
    echo '<h1 class="title">Cintorín:</h1>';
    
    echo '<div id="cinterhra">',"\n";
    function generujCinterRiadok($cislofarby,$menonaroda,$obkec) {
      echo '  <div class="cinterriadok">',"\n";
        echo '    <div class="hra-hrac-',$cislofarby,'" style="border: 1px solid black; margin-right: 5px;"></div>',"\n";
        echo '    <b>',$menonaroda,'</b>',"\n";
        echo '    <br /><i>',$obkec,'</i>',"\n";
      echo '  </div><br />',"\n";
    }
    generujCinterRiadok(3, 'Škriatkovia',                 '"Žaby ich dali."');
    generujCinterRiadok(5, 'Jokiho super národ',          '"Boli až príliš super pre tento svet."');
    generujCinterRiadok(2, 'U-chaorm',                    '"Stali sa medzinárodnou špecialitou."');
    generujCinterRiadok(5, 'Pokusní Kralici',             '"Zasýtili gobliniu dedinu a zmizli pod vodou."');
    
    generujCinterRiadok(11,'Sekularizovaní eschatologici','"Ich mágia bola proti šmolkom bezmocná."');
    generujCinterRiadok(2, 'Veľmi (veľmi...) naštvaný národ VEĽKÝCH krollov, ktorí boli kamaráti so Sekularizovanými Eschatologikmi :P','"Hnev ich kváril jedno kolo."');
    
    generujCinterRiadok(9, 'Šmatláci',                    '"Diplomacia nebola ich silnou stránkou."');
    generujCinterRiadok(6, 'Snežní Elfovia',              '"Ich snehuliaci privolali na nich skazu."');
    generujCinterRiadok(6, 'Vatikán',                     '"O čom dávaš, prečo Vatikán?"');
    generujCinterRiadok(3, 'nuda',                        '"Ich pláže vzbudzovali verejné pohoršenie."');
    generujCinterRiadok(13,'dvojmetroví trpaslíci',       '"Obrástli ich nemilosrdné huby"');
    generujCinterRiadok(1, 'Rudý rád',                    '"Obrástli ich nemilosrdné huby 2"');

    echo '</div><br /><br />';
    /*echo '<br /><code>Čo myslíte, čo by malo ísť pod mená? rozmýšľal som buď nejaká zaujímavosť národa,
    dátum vyhynutia, integrál počtu územii podľa času alebo počet území za največieho rozmachu,
     ale kto si takéto veci pametá? Databáza žiaľ nie.</code>';*/
    echo '<br /><code>Ak je nejaká informácia nepresná, nebojte sa informovať vo fóre (chýba národ/zlá farba/zlé poradie)</code>';
  } else {
    function r_hlavna_hra() {}
    
    /*$today = getdate(); //$today['hours']=5;
    if ($today['mday']<20) {
      if ($_SESSION['admin']!=1) {echo 'hra sa začne s úderom polnoci'; die;}
    }*/
    
    //include 'uprav.php';
    echo '<h1 class="title">Hra :: mapa</h1>'."\n";
    echo '<div class="hra-sajdpanel">';
    
    
    // oznamy
    $oznamy='';
    if ($status=='playing') {
      
      $h=MySQL_query("SELECT id FROM hra_hrac WHERE meno='".$_SESSION['logname']."';");
      $hracid=MySQL_result($h,0);
      $ozq=MySQL_query("SELECT * FROM hra_oznamy WHERE hrac=".$hracid.";"); 
      while ($hr=MySQL_fetch_array($ozq)) {
        $oznamy.= '<div style="border: 5px solid white;">'.$hr['oznam'].'</div>';
      }
      if ($oznamy!='') {$oznamy='<hr />'.$oznamy;}
    }
    
    // predpoved
    $qp=MySQL_query("SELECT stav FROM hra_wedr WHERE meno='aktualne';");
    $wedr=MySQL_result($qp,0);
    
    $today = getdate(); //$today['hours']=5;
    if ((($today['hours']>21) or ($today['hours']<6)) and false) {
      if ($wedr=='jasno')  $wedr='noc';
      else $wedr='nocobl';
    }
    
    $wedre='<div>';
    $wedre.='<img src="blog/wedr-'.$wedr.'.png" style="float: left;" alt="'.$wedr.'" />';
    $qp=MySQL_query("SELECT stav FROM hra_wedr WHERE meno='".$wedr."';");
    $wedre.='<strong>Poveď počasia</strong><br /><br />';
    $wedre.=MySQL_result($qp,0);
    $wedre.='<br style="clear: both;" /></div>';
    
    // personalne spravy mne
    $q=MySQL_query("SELECT cislo FROM hra_udaje WHERE meno='stavhry';");
    $stavhry=MySQL_result($q,0);
    
    $qp=MySQL_query("SELECT id FROM hra_hrac WHERE meno='".mysql_escape_string($_SESSION['logname'])."';");
    $hracid=MySQL_result($qp,0);
    $qp=MySQL_query("SELECT * FROM hra_pm WHERE komumeno='".mysql_escape_string($_SESSION['logname'])."' AND stavhry=".($stavhry-1).";");
    $spravy='';
    
    
      
        
    while($spr=MySQL_fetch_array($qp)) {
      $kt=MySQL_query("SELECT narod FROM hra_hrac WHERE meno='".mysql_escape_string($spr['ktomeno'])."';");
      if ($m=MySQL_result($kt,0)) $ktomi=$m; else $ktomi=$spr['ktomeno'];
      
      $spravy.='<div>';
      $spravy.='<img src="blog/ico_chat.png" style="float: left; margin-right: 5px;" alt="správa" />';
      //$kt=MySQL_query("SELECT meno FROM hra_hrac WHERE id=".$spr['kto'].";");
      $spravy.='<strong>'.$ktomi.'</strong>: '.htmlspecialchars($spr['sprava']) ;
      $spravy.='<br style="clear: both;" /></div>';
    }
    if ($spravy!='') {$spravy='<hr />'.$spravy;}
    
    // personalne spravy odo mna
    $qp=MySQL_query("SELECT * FROM hra_pm WHERE ktomeno='".mysql_escape_string($_SESSION['logname'])."' AND stavhry=".$stavhry.";");
    $spravy2='';
    while($spr=MySQL_fetch_array($qp)) {
      $spravy2.='<div>';
      $spravy2.='<img src="blog/ico_archiv.png" style="float: left; margin-right: 5px;" alt="správa" />';
      if ($spr['komumeno']=='*') {$komusom='Herné fórum';}
      else {
        $kt=MySQL_query("SELECT narod FROM hra_hrac WHERE meno='".mysql_escape_string($spr['komumeno'])."';");
        if ($m=MySQL_result($kt,0)) {
          $komusom=$m;
        } else {
          $komusom=$spr['komumeno'];
        } 
        //MySQL_result($kt,0);
      }
      $spravy2.='Správa pre <strong>'.$komusom.'</strong>: '.htmlspecialchars($spr['sprava']).($spr['komumeno']=='*'?'':' <a href="hra.php?action=zrusspravuw&amp;sprava='.$spr['id'].'">[zrušiť]</a>') ;
      $spravy2.='<br style="clear: both;" /></div>';
    }
    if ($spravy2!='') {$spravy2='<hr />'.$spravy2;}
    
    // zoznam hracov
    $hracq=MySQL_query("SELECT * FROM hra_hrac"); $zoznam='';
    while ($hr=MySQL_fetch_array($hracq)) {
      $beklas='hrac-'.$hr['farba'];
      $zoznam.= '<div';
      if ($hr['meno']==$_SESSION['logname'])  {$zoznam.= ' style="border: 5px solid orange;"';}
      else                                    {$zoznam.= ' style="border: 5px solid white;"';}
      $zoznam.= '><div style="float: left; width: 32px; height:32px; border: 1px solid black; margin-right: 5px;" class="hra-'.$beklas.'"></div>';
      $zoznam.= 'hráč: <strong>'.$hr['meno'].'</strong>';
      
      //$q=MySQL_query("SELECT COUNT(*) FROM hra_akcie WHERE hrac=".$hr['id'].";");
      //if ($_SESSION['admin']==1 and MySQL_result($q,0)>0) $zoznam.= ' * ';
      
      if ($_SESSION['admin']==1) {
        $zoznam.= ' ';
        $q=MySQL_query("SELECT COUNT(*) FROM hra_pm WHERE komumeno='".mysql_escape_string($hr['meno'])."' AND stavhry=($stavhry-1);");
        if (MySQL_result($q,0)>0) $zoznam.= 'R';
        $q=MySQL_query("SELECT COUNT(*) FROM hra_pm WHERE ktomeno='".mysql_escape_string($hr['meno'])."' AND stavhry=$stavhry;");
        if (MySQL_result($q,0)>0) $zoznam.= 'S';
        $q=MySQL_query("SELECT COUNT(*) FROM hra_akcie WHERE hrac=".$hr['id'].";");
        if (MySQL_result($q,0)>0) $zoznam.= 'A';
        $zoznam.= ' ';
      }
      
      $zoznam.= '<br />národ: '.$hr['narod'].'';
      if ($_SESSION['admin']==1) {
        $zoznam.= '<br />akcie: <a href="hra.php?action=preloginw&amp;prelog='.$hr['meno'].'">prelog</a>';
        if ($hr['meno']==$_SESSION['logname']) {
          $zoznam.=' - <a href="hra-zmaz">zmazať hráča</a>';
          $zoznam.=' - <a href="hra-starsie">všetky správy</a>';
        }
      } elseif ($hr['meno']==$_SESSION['logname']) {
        $zoznam.= '<br />akcie: ';
        $zoznam.=' <a href="hra-zmaz">zmazať hráča</a>';
        $zoznam.=' - <a href="hra-starsie">všetky správy</a>';
      }
      
      $zoznam.= '<br style="clear: both;" /></div>';
    }
    
    
    if ($_SESSION['logged']!=1) {
      echo '<div>Vitaj v Hre!</div>';
      echo '<div>Žiaľ, niesi prihlásený a teda nemôžeš hrať, len sa prizerať, ako sa ostatní zabávajú. Takisto nevidíš a nemôžeš prispievať na herné fórum, čo sa umožní až po prihlásení</div>';
      echo '<hr />',$wedre,'<hr />',$zoznam;
    } elseif ($status=='full') {
      echo '<div>Vitaj v Hre!</div>';
      echo '<div>Žiaľ, hru už hrajú 13 ľudia, čo je maximum, nemôžeš sa zapojiť.</div>';
      echo '<hr />',$wedre,'<hr />',$zoznam;
    } elseif ($status=='nothing') {
      echo '<div>Vitaj v Hre, <b>'.$_SESSION['logname'].'</b>!</div>';
      //echo '<div>Výborne, si prihlásený - na zapojenie do hry ti stačí vytvoriť svoj <a href="hra-narod">národ</a> za ktorý budeš hrať</div>';
      echo '<div>Žiaľ hra sa končí a už nieje možné sa prihlásiť. Možno v budúcnosti prijde eidžof znovu k životu.</div>';
      echo '<hr />',$wedre,'<hr />',$zoznam;
    } elseif ($status=='created') {
      echo '<div>Vitaj dobrodruhu</div>';
      //echo '<div>Teraz sa treba niekde usadiť, vytvoriť základňu, z ktorej dobyješ ďalšie územia. Ako svoje prvotné políčko môžeš určiť ktorúkoľvek súš (sivá), ktorá ešte nieje obsadená iným hráčom.</div>';
      echo '<div>Žiaľ hra sa končí a už nieje možné sa prihlásiť. Možno v budúcnosti prijde eidžof znovu k životu.</div>';
      echo '<hr />',$wedre,'<hr />',$zoznam;
    } elseif ($status=='playing') {
      //echo '<div>Vitaj dobrodruhu</div>';
      //echo '<div>pome hrať</div>';
      echo $wedre;
      echo $oznamy,'<hr />',$zoznam,$spravy,$spravy2;
    }
    echo '</div>';
    
    $q=MySQL_query("SELECT * FROM hra_hrac WHERE meno='".mysql_escape_string($_SESSION['logname'])."';");
    $ja=MySQL_fetch_array($q);
    
    $q=MySQL_query('SELECT * FROM hra_teren ORDER BY y ASC, x ASC;');
    
    echo '<div id="hra" class="hra-mainpanel">';
    for ($y=0;$y<16;$y++) {
      echo '<div class="riadok" id="riadok'.$y.'">';
      for ($x=0;$x<16;$x++) {
        $uz="[$x,$y]";
        $fot=MySQL_fetch_array($q);
        if ($fot['teren']==0) {
          $beklas='teren-voda';
        } else {
          if ($fot['hrac']==0) {
            $beklas='teren-sucho';
          } else {
            $farq=MySQL_query("SELECT farba FROM hra_hrac WHERE id=".mysql_escape_string($fot['hrac']).';' );
            $far=MySQL_result($farq,0);
            $beklas='hrac-'.$far;
          }
        }
        echo '<div class="hra-'.$beklas.'" id="bunka'.$x.'-'.$y.'" title="'.$uz.'">';
        if ($status=='created') {
          if (($fot['teren']==1) and ($fot['hrac']==0)) {
            echo '<a href="hra.php?action=usadw&amp;mx='.$x.'&amp;my='.$y.'" title="usaď sa na území '.$uz.'">&nbsp;B</a>';
          }
        } elseif ($status=='playing') {
          if ($fot['teren']==1) {
            $akcq=MySQL_query("SELECT typ FROM hra_akcie WHERE x='".mysql_escape_string($x)."' AND y='".mysql_escape_string($y)."' AND hrac='".mysql_escape_string($ja['id'])."' AND (typ='doby' OR typ='obran' OR typ='' OR typ='nehaj');");
            if ($akcq) {
              $tuto=MySQL_result($akcq,0);
            } else {$tuto='';}
            
            $akcq=MySQL_query("SELECT COUNT(*) FROM hra_akcie WHERE hrac='".mysql_escape_string($ja['id'])."' AND typ='obran';");
            $obran=MySQL_result($akcq,0);
            $akcq=MySQL_query("SELECT COUNT(*) FROM hra_akcie WHERE hrac='".mysql_escape_string($ja['id'])."' AND typ='doby';");
            $doby=MySQL_result($akcq,0);
            
            //$tuakcia=MySQL_query("SELECT typ FROM hra_akcie WHERE hrac='".mysql_escape_string($ja['id'])."' AND x=''")
            $zabaq=MySQL_query("SELECT COUNT(*) FROM hra_akcie WHERE x=".mysql_escape_string($x)." AND y=".mysql_escape_string($y)." AND typ='zaba';");
            $pocetziab=MySQL_result($zabaq,0);
            
            if ($tuto=='obran') {
              // zmaz akciu
              //echo '';
              
              if ($tuto=='doby') {$obr='<img src="blog/ico_sword.png" style="position:relative; z-index:2;" />';} 
              //<img src="blog/ico_zaba.png" style="position:relative; z-index:1; margin-left: -32px;" />
              elseif ($tuto=='obran') {$obr='<img src="blog/ico_shield.png" style="position:relative; z-index:2;" />';} 
              else {$obr='&nbsp;';} 
              
              
              if ($pocetziab==1) 
                $obr.='<img src="blog/ico_zaba.png" style="position:relative; z-index:1; margin-left: -32px;" />';
              //if (($x==7) && ($y==11)) $obr.='<img src="blog/ico_slon.png" style="position:relative; z-index:2; margin-left: -32px;" />';
              echo '<a href="hra.php?action=dobyw&amp;mx='.$x.'&amp;my='.$y.'" title="prenechaj územie">'.$obr.'</a>';
              
            } elseif ($tuto!='') {
              // zmaz akciu
              //echo '';
              
              if ($tuto=='doby') {$obr='<img src="blog/ico_sword.png" style="position:relative; z-index:2;" />';} 
              //<img src="blog/ico_zaba.png" style="position:relative; z-index:1; margin-left: -32px;" />
              elseif ($tuto=='obran') {$obr='<img src="blog/ico_shield.png" style="position:relative; z-index:2;" />';} 
              elseif ($tuto=='nehaj') {$obr='<img src="blog/ico_next.png" style="position:relative; z-index:2;" />';} 
              else {$obr='&nbsp;';} 
              
              
              if ($pocetziab==1) 
                $obr.='<img src="blog/ico_zaba.png" style="position:relative; z-index:1; margin-left: -32px;" />';
              //if (($x==7) && ($y==11)) $obr.='<img src="blog/ico_slon.png" style="position:relative; z-index:2; margin-left: -32px;" />';
              echo '<a href="hra.php?action=dobyw&amp;mx='.$x.'&amp;my='.$y.'" title="odstráň akciu">'.$obr.'</a>';
              
            } elseif  ($fot['hrac']==$ja['id']) {
              //obran
              $obr='';
              if ($pocetziab==1) $obr='<img src="blog/ico_zaba.png" />';
              //if (($x==7) && ($y==11)) $obr.='<img src="blog/ico_slon.png" style="position:relative; z-index:2; " />';
              else $obr='&nbsp;';
              if (($obran<2) and ($obran+$doby<3)) {
                $cele='<a href="hra.php?action=dobyw&amp;mx='.$x.'&amp;my='.$y.'" title="obraňuj '.$uz.'">'.$obr.'</a>';
              } else {
                $cele=$obr;
              }
              echo $cele;
              
              //echo '&nbsp;';
            } else {
              //doby
              if ($pocetziab==1) $obr='<img src="blog/ico_zaba.png" />';
              //else if (($x==7) && ($y==11)) $obr='<img src="blog/ico_slon.png" style="position:relative; z-index:2; " />';
              else $obr='&nbsp;';
              
              $cele=$obr;
              if (($doby<2) and ($obran+$doby<3)) {
                $qvedla=MySQL_query("SELECT COUNT(*) FROM hra_teren WHERE hrac='".mysql_escape_string($ja['id'])."' AND ((x='".($x-1)."' AND y='".($y)."') OR (x='".($x+1)."' AND y='".($y)."') OR (x='".($x)."' AND y='".($y-1)."') OR (x='".($x)."' AND y='".($y+1)."'));");  
                $pvedla=MySQL_result($qvedla,0); // pocet policok tvojich povedla
                if ($pvedla>0) {
                  $cele='<a href="hra.php?action=dobyw&amp;mx='.$x.'&amp;my='.$y.'" title="zaútoč na '.$uz.'">'.$obr.'</a>';
                }
                
              }
              echo $cele;
            }
          }
        }
        echo '</div>';
      }
      echo '</div>';
    }
    echo '</div>';
  }
  Mysql_close($spoj);
  
  function comment_mysql_tables() {}
  /*
    TABLE hra_hrac {
      int id;
      text meno;
      int farba;
      text narod;
    }
    TABLE hra_farby {
      int id;
      text farba;
      int hrac;
    }
    TABLE hra_teren {
      int x;
      int y;
      int teren; (0-voda/1-teren)
      int hrac;
    }
    TABLE hra_akcie {
      int hrac;
      text typ; (obran/doby)
      int y;
      int x;
    }
    TABLE hra_ooznamy {
      int hrac;
      text oznam;
    }
  */
?>