<?php
/*
 * smtp.php
 *
 * @(#) $Header: /cvsroot/PHPlibrary/smtp.php,v 1.10 2000/06/03 02:29:41 mlemos Exp $
 *
 */

class smtp_class
{
 var $host_name="";
 var $host_port=25;
 var $localhost="";
 var $timeout=0;
 var $error="";
 var $debug=0;
 var $esmtp=1;
 var $esmtp_host="";
 var $esmtp_extensions=array();
 var $maximum_piped_recipients=100;

 /* private variables - DO NOT ACCESS */

 var $state="Disconnected";
 var $connection=0;
 var $pending_recipients=0;

 /* Private methods - DO NOT CALL */

 Function OutputDebug($message)
 {
  echo $message,"\n";
 }

 Function GetLine()
 {
  for($line="";;)
  {
   if(feof($this->connection))
   {
    $this->error="reached the end of stream while reading from socket";
    return(0);
   }
   if(($data=fgets($this->connection,100))==false)
   {
    $this->error="it was not possible to read line from socket";
    return(0);
   }
   $line.=$data;
   $length=strlen($line);
   if($length>=2
   && substr($line,$length-2,2)=="\r\n")
   {
    $line=substr($line,0,$length-2);
    if($this->debug)
     $this->OutputDebug("< $line");
    return($line);
   }
  }
 }

 Function PutLine($line)
 {
  if($this->debug)
   $this->OutputDebug("> $line");
  if(!fputs($this->connection,"$line\r\n"))
  {
   $this->error="it was not possible to write line to socket";
   return(0);
  }
  return(1);
 }

 Function PutData(&$data)
 {
  if(strlen($data))
  {
   if($this->debug)
    $this->OutputDebug("> $data");
   if(!fputs($this->connection,$data))
   {
    $this->error="it was not possible to write data to socket";
    return(0);
   }
  }
  return(1);
 }

 Function VerifyResultLines($code,&$responses)
 {
  if(GetType($responses)!="array")
   $responses=array();
  Unset($match_code);
  while(($line=$this->GetLine($this->connection)))
  {
   if(IsSet($match_code))
   {
    if(strcmp(strtok($line," -"),$match_code))
    {
     $this->error=$line;
     return(0);
    }
   }
   else
   {
    $match_code=strtok($line," -");
    if(GetType($code)=="array")
    {
     for($codes=0;$codes<count($code) && strcmp($match_code,$code[$codes]);$codes++);
     if($codes>=count($code))
     {
      $this->error=$line;
      return(0);
     }
    }
    else
    {
     if(strcmp($match_code,$code))
     {
      $this->error=$line;
      return(0);
     }
    }
   }
   $responses[]=strtok("");
   if(!strcmp($match_code,strtok($line," ")))
    return(1);
  }
  return(-1);
 }

 Function FlushRecipients()
 {
  if($this->pending_sender)
  {
   $r="";
   if($this->VerifyResultLines("250",$r)<=0)
    return(0);
   $this->pending_sender=0;
  }
  for(;$this->pending_recipients;$this->pending_recipients--)
  {
   $r="251";
   if($this->VerifyResultLines(array("250"),$r)<=0)
    return(0);
  }
  return(1);
 }

 /* Public methods */

 Function Connect()
 {
  $this->error=$error="";
   $this->esmtp_host="";
   $this->esmtp_extensions=array();
//  if(!($this->connection=($this->timeout ? fsockopen($this->host_name,$this->host_port,&$errno,&$error,$this->timeout) : fsockopen($this->host_name,$this->host_port))))
  if(!($this->connection=($this->timeout ? fsockopen($this->host_name,$this->host_port,$errno,$error,$this->timeout) : fsockopen($this->host_name,$this->host_port))))
  {
   switch($error)
   {
    case -3:
     $this->error="-3 socket could not be created";
     return(0);
    case -4:
     $this->error="-4 dns lookup on hostname \"".$host_name."\" failed";
     return(0);
    case -5:
     $this->error="-5 connection refused or timed out";
     return(0);
    case -6:
     $this->error="-6 fdopen() call failed";
     return(0);
    case -7:
     $this->error="-7 setvbuf() call failed";
     return(0);
    default:
     $this->error=$error." could not connect to the host \"".$this->host_name."\"";
     return(0);
   }
  }
  else
  {
   if(!strcmp($localhost=$this->localhost,"")
   && !strcmp($localhost=getenv("SERVER_NAME"),"")
   && !strcmp($localhost=getenv("HOST"),""))
     $localhost="localhost";
    $success=0;
    $r="";
    if($this->VerifyResultLines("220",$r)>0)
    {
     if($this->esmtp)
     {
      $responses=array();
      if($this->PutLine("EHLO $localhost")
      && $this->VerifyResultLines("250",$responses)>0)
      {
       $this->esmtp_host=strtok($responses[0]," ");
       for($response=1;$response<count($responses);$response++)
       {
        $extension=strtoupper(strtok($responses[$response]," "));
        $this->esmtp_extensions[$extension]=strtok("");
       }
       $success=1;
      }
     }
     $r="";
     if(!$success
     && $this->PutLine("HELO $localhost")
     && $this->VerifyResultLines("250",$r)>0)
      $success=1;
   }
   if($success)
   {
    $this->state="Connected";
    return(1);
   }
   else
   {
    fclose($this->connection);
    $this->connection=0;
    $this->state="Disconnected";
    return(0);
   }
  }
 }

 Function MailFrom($sender)
 {
  if(strcmp($this->state,"Connected"))
  {
   $this->error="connection is not in the initial state";
   return(0);
  }
  $this->error="";
  if(!$this->PutLine("MAIL FROM:<$sender>"))
   return(0);
  $r="";
  if(!IsSet($this->esmtp_extensions["PIPELINING"])
  && $this->VerifyResultLines("250",$r)<=0)
   return(0);
  $this->state="SenderSet";
  if(IsSet($this->esmtp_extensions["PIPELINING"]))
   $this->pending_sender=1;
  $this->pending_recipients=0;
  return(1);
 }

 Function SetRecipient($recipient)
 {
  switch($this->state)
  {
   case "SenderSet":
   case "RecipientSet":
    break;
   default:
    $this->error="connection is not in the recipient setting state";
    return(0);
  }
  $this->error="";
  if(!$this->PutLine("RCPT TO:<$recipient>"))
   return(0);
  if(IsSet($this->esmtp_extensions["PIPELINING"]))
  {
   $this->pending_recipients++;
   if($this->pending_recipients>=$this->maximum_piped_recipients)
   {
    if(!$this->FlushRecipients())
     return(0);
   }
  }
  else
  {
   $r="251";
   if($this->VerifyResultLines(array("250"),$r)<=0)
    return(0);
  }
  $this->state="RecipientSet";
  return(1);
 }

 Function StartData()
 {
  if(strcmp($this->state,"RecipientSet"))
  {
   $this->error="connection is not in the start sending data state";
   return(0);
  }
  $this->error="";
  if(!$this->PutLine("DATA"))
   return(0);
  if($this->pending_recipients)
  {
   if(!$this->FlushRecipients())
    return(0);
  }
  $r="";
  if($this->VerifyResultLines("354",$r)<=0)
   return(0);
  $this->state="SendingData";
  return(1);
 }

 Function PrepareData($data,&$output)
 {
  $length=strlen($data);
  for($output="",$position=0;$position<$length;)
  {
   $next_position=$length;
   for($current=$position;$current<$length;$current++)
   {
    switch($data[$current])
    {
     case "\n":
      $next_position=$current+1;
      break 2;
     case "\r":
      $next_position=$current+1;
      if($data[$next_position]=="\n")
       $next_position++;
      break 2;
    }
   }
   if($data[$position]==".")
    $output.=".";
   $output.=substr($data,$position,$current-$position)."\r\n";
   $position=$next_position;
  }
 }

 Function SendData($data)
 {
  if(strcmp($this->state,"SendingData"))
  {
   $this->error="connection is not in the sending data state";
   return(0);
  }
  $this->error="";
  return($this->PutData($data));
 }

 Function EndSendingData()
 {
  if(strcmp($this->state,"SendingData"))
  {
   $this->error="connection is not in the sending data state";
   return(0);
  }
  $this->error="";
  $r="";
  if(!$this->PutLine("\r\n.")
  || $this->VerifyResultLines("250",$r)<=0)
   return(0);
  $this->state="Connected";
  return(1);
 }

 Function ResetConnection()
 {
  switch($this->state)
  {
   case "Connected":
    return(1);
   case "SendingData":
    $this->error="can not reset the connection while sending data";
    return(0);
   case "Disconnected":
    $this->error="can not reset the connection before it is established";
    return(0);
  }
  $this->error="";
  $r="";
  if(!$this->PutLine("RSET")
  || $this->VerifyResultLines("250",$r)<=0)
   return(0);
  $this->state="Connected";
  return(1);
 }

 Function Disconnect($quit=1)
 {
  if(!strcmp($this->state,"Disconnected"))
  {
   $this->error="it was not previously established a SMTP connection";
   return(0);
  }
  $this->error="";
  $r="";
  if(!strcmp($this->state,"Connected")
  && $quit
  && (!$this->PutLine("QUIT")
  || $this->VerifyResultLines("221",$r)<=0))
   return(0);
  fclose($this->connection);
  $this->connection=0;
  $this->state="Disconnected";
  return(1);
 }

 Function SendMessage($sender,$recipients,$headers,$body)
 {
  if(($success=$this->Connect()))
  {
   if(($success=$this->MailFrom($sender)))
   {
    for($recipient=0;$recipient<count($recipients);$recipient++)
    {
     if(!($success=$this->SetRecipient($recipients[$recipient])))
      break;
    }
    if($success
    && ($success=$this->StartData()))
    {
     for($header_data="",$header=0;$header<count($headers);$header++)
      $header_data.=$headers[$header]."\r\n";
     if(($success=$this->SendData($header_data."\r\n")))
     {
      $this->PrepareData($body,$body_data);
      $success=$this->SendData($body_data);
     }
     if($success)
      $success=$this->EndSendingData();
    }
   }
   $error=$this->error;
   $disconnect_success=$this->Disconnect($success);
   if($success)
    $success=$disconnect_success;
   else
    $this->error=$error;
  }
  return($success);
 }

};

?>
