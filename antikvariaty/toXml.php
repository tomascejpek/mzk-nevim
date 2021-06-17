<?php

$opt = getopt("i:o:");

if (isset($opt['i'])) $in_file = $opt['i'];
else die("-i: Input file is not set!\n");
if (isset($opt['o'])) $out_file = $opt['o'];
else die("-o: Output file is not set!\n");

$tags = [
    "nbn" => "015  a",
    "isbn" => "020  a",
    "issn" => "022  a",
    "ctlno" => "024  8",
    "author" => "100  a",
    "title" => "245  a",
    "date" => "260  c",
    "url" => "856  u"
];

$xmlReader = simplexml_load_file($in_file, 'SimpleXMLElement', LIBXML_NOCDATA);

$xmlWriter = new XMLWriter();
$xmlWriter->openURI($out_file);
$xmlWriter->startDocument('1.0', 'UTF-8');
$xmlWriter->setIndent(true);

$xmlWriter->startElement('collection');

foreach ($xmlReader->record as $record) {

    $xmlWriter->startElement('record');
    $xmlWriter->writeAttribute('xmlns', "http://www.loc.gov/MARC21/slim");
    $xmlWriter->writeAttribute('xmlns:xsi', "http://www.w3.org/2001/XMLSchema-instance");
    $xmlWriter->writeAttribute('xsi:schemaLocation', "http://www.loc.gov/MARC21/slim http://www.loc.gov/standards/marcxml/schema/MARC21slim.xsd");
    $xmlWriter->startElement('controlfield');
    $xmlWriter->writeAttribute('tag', '001');
    $xmlWriter->writeRaw($record->identifier);
    $xmlWriter->endElement();
    foreach ($tags as $oldTag => $newTag) {
        $ind1 = substr($newTag, 3, 1);
        $ind2 = substr($newTag, 4, 1);
        $code = substr($newTag, 5, 1);
        $tag = substr($newTag, 0, 3);
        if ($record->$oldTag) {
            $xmlWriter->startElement('datafield');
            $xmlWriter->writeAttribute('tag', $tag);
            $xmlWriter->writeAttribute('ind1', $ind1);
            $xmlWriter->writeAttribute('ind2', $ind2);
            $duplicated = [];
            foreach ($record->$oldTag as $rec) {
                if ($oldTag == "ctlno") {
                    $rec = substr($rec, strpos($rec, ")") + 1);
                    if ($rec == "") continue;
                    if (array_key_exists($rec, $duplicated)) continue;
                    array_push($duplicated, $rec);
                }
                $xmlWriter->startElement('subfield');
                $xmlWriter->writeAttribute('code', $code);
                $xmlWriter->writeRaw(htmlspecialchars($rec));
                $xmlWriter->endElement();

            }
            $xmlWriter->endElement();
        }
    }
    $xmlWriter->endElement();
}
$xmlWriter->endElement();

?>
