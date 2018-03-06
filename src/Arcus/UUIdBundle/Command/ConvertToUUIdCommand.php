<?php

namespace Arcus\UUIdBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use ZipArchive;

class ConvertToUUIdCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('UUId:Convert')
            ->setDescription('Convert pdf files from destination folder to uuid xml file and prepare zip file.')
            ->addArgument(
                'destination',
                InputArgument::REQUIRED,
                'Pdf files folder destination'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('destination');
        if(!file_exists ( $path )){
            $output->writeln('Folder does not exist!');die();
        }
        $zip = new ZipArchive();

        $DelFilePath = "files.zip";


        if(file_exists($path.'/'.$DelFilePath)) {

                unlink ($path.'/'.$DelFilePath); 

        }
        if ($zip->open($path.'/'.$DelFilePath, ZIPARCHIVE::CREATE) != TRUE) {
                die("Could not open archive");
        }
        $files = array_diff(scandir($path), array('.', '..'));
        $file_count = count( $files );
        foreach ($files as $key => $file) {
            $file_parts = pathinfo($file);
            if ($file_parts['extension'] == 'pdf') {
                $date = gmDate("Y-m-d\TH:i:s\Z"); 
                $uniqueName = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
                mt_rand( 0, 0xffff ),
                mt_rand( 0, 0x0fff ) | 0x4000,
                mt_rand( 0, 0x3fff ) | 0x8000,
                mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ));

                $base64Pdf = base64_encode(file_get_contents($path.'/'.$file));

                $xmlTemp = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <Attachment action="CREATE">
                    <AttachmentID>'.$uniqueName.'</AttachmentID>                    
                    <Filename>'.$file_parts['basename'].'</Filename>                    
                    <Content>'.$base64Pdf.'</Content>                    
                    <ContentType>application/pdf</ContentType>                    
                    <Confidential>false</Confidential>                    
                </Attachment>';

                                    $xmlTemp2 = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>                    
                <SubmitterFile>                    
                    <applicationVersion>1.2.3</applicationVersion>                    
                    <uuid>'.$uniqueName.'</uuid>                    
                    <status>VALIDATED</status>
                    <Attachment>
                        <AttachmentID>'.$uniqueName.'</AttachmentID>
                        <Filename>'.$file_parts['basename'].'</Filename>
                        <ContentType>application/pdf</ContentType>                    
                        <Confidential>false</Confidential>                    
                    </Attachment>                    
                    <exportDate>'.$date.'</exportDate>                    
                    <referenceUuids/>                    
                </SubmitterFile>';
                //Log original names with generated uuid to tmp file
                $tmpToLog = date("r") . "  |  ORIGINAL NAME: " . $file_parts['basename'] . " UUID: " . $uniqueName;
                file_put_contents($path . '/uuid.log', $tmpToLog . "\n", FILE_APPEND);
                $tmpToLog = $file_parts['basename'] . ";". $uniqueName.";";
                file_put_contents($path . '/uuid.csv', $tmpToLog . "\n", FILE_APPEND);

                $tmp_file_pdf[$key] = tempnam ($path,'pdf');
                $tmp_file_xml[$key] = tempnam ($path,'xml');
                $tmp_file_xml2[$key] = tempnam ($path,'xml');

                file_put_contents($tmp_file_pdf[$key],file_get_contents($path.'/'.$file));
                file_put_contents($tmp_file_xml[$key],$xmlTemp);
                file_put_contents($tmp_file_xml2[$key],$xmlTemp2);

                $zip->addFile($tmp_file_pdf[$key],"data/files/".$uniqueName.".pdf");
                $zip->addFile($tmp_file_xml[$key],"export/".$uniqueName.".xml");
                $zip->addFile($tmp_file_xml2[$key],"data/".$uniqueName.".xml");    
            }else{
                $output->writeln('Wrong file extesion! Only pdf files are accepted!');
                foreach ($files as $key => $value) {
                    if (isset($tmp_file_xml[$key])) {
                        unlink($tmp_file_xml[$key]);
                        unlink($tmp_file_xml2[$key]);
                        unlink($tmp_file_pdf[$key]);
                    }
                }
                die();
            }
        }
        $zip->close(); 
        
        foreach ($files as $key => $value) {
            unlink($tmp_file_xml[$key]);
            unlink($tmp_file_xml2[$key]);
            unlink($tmp_file_pdf[$key]);
        }
        $output->writeln('Zip file prepared!');die();
    }
}
