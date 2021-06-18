<?php

$opt = getopt("i:o:t:");

if (isset($opt['i'])) $inFile = $opt['i'];
else die("-i: Input file is not set!\n");
if (isset($opt['o'])) $outFile = $opt['o'];
else die("-o: Output file is not set!\n");
if (isset($opt['t'])) $recordType = $opt['t'];
else die("-t: Type is not set!\n");

// <subfield code="$code">$value</subfield>
function writeSubField($code, $value)
{
    global $xmlWriter;
    $xmlWriter->startElement('subfield');
    $xmlWriter->writeAttribute('code', $code);
    $xmlWriter->writeRaw($value);
    $xmlWriter->endElement();
}

function writeDataField($tag, $ind1, $ind2, $data, $type = "n")
{
    global $xmlWriter;
    $xmlWriter->startElement('datafield');
    $xmlWriter->writeAttribute('tag', $tag);
    $xmlWriter->writeAttribute('ind1', $ind1);
    $xmlWriter->writeAttribute('ind2', $ind2);

    if (is_array($data)) {
        if ($type === "rs") { // repeat subfields -> 505 $t$g$t$g$t$g
            foreach ($data as $subfield) {
                foreach ($subfield as $code => $value) {
                    if (is_array($value)) {
                        foreach ($value as $val) {
                            writeSubField($code, $val);
                        }
                    } else {
                        writeSubField($code, $value);
                    }
                } // foreach $subfield
            } // foreach $data
        } // if "rs"
        else {
            foreach ($data as $code => $value) {
                if (is_array($value)) {
                    foreach ($value as $val) {
                        writeSubField($code, $val);
                    }
                } else {
                    writeSubField($code, $value);
                }
            }
        } // else "rs"
    } // if is_array
    /*   else{
         writeSubField($code, $data);
       }*/
    $xmlWriter->endElement();
}

function writeField($result)
{
    global $dataFields;
    global $xmlWriter;
    foreach ($dataFields as $oldTag => $newTag) {
        $ind1 = substr($newTag, 3, 1);
        $ind2 = substr($newTag, 4, 1);
        $tag = substr($newTag, 0, 3);
        if (($len = strlen($newTag)) > 5) {
            $type = substr($newTag, 5, $len - 5);
        } else $type = "n"; // normal
        if (!array_key_exists($oldTag, $result)) continue;

        if ($type === "r") { // repeated field
            foreach ($result[$oldTag] as $field) {
                if ($field) {
                    writeDataField($tag, $ind1, $ind2, $field);
                }
            }
        } else writeDataField($tag, $ind1, $ind2, $result[$oldTag], $type);
    }
}

function writeControlFields($result)
{
    global $controlFields;
    global $xmlWriter;
    foreach ($controlFields as $key) {
        if (array_key_exists($key, $result)) {
            $xmlWriter->startElement('controlfield');
            $xmlWriter->writeAttribute('tag', $key);
            $xmlWriter->writeRaw($result[$key]);
            $xmlWriter->endElement();
        }
    }
}

function parseDate($date)
{
    if (preg_match('/.*(\d{4}).*/', $date, $matches)) {
        return $matches[1];
    }
    return "----";
}

$begin = time();

if (file_exists($inFile)) {
    $in = fopen($inFile, "r");
} else {
    echo "Soubor $inFile neexistuje\n";
    exit();
}

$xmlWriter = new XMLWriter();
$xmlWriter->openURI($outFile);
$xmlWriter->startDocument('1.0', 'UTF-8');
$xmlWriter->setIndent(true);
$xmlWriter->startElement('collection');
$xmlWriter->writeAttribute('xmlns', "http://www.loc.gov/MARC21/slim");
$xmlWriter->writeAttribute('xmlns:xsi', "http://www.w3.org/2001/XMLSchema-instance");
$xmlWriter->writeAttribute('xsi:schemaLocation', "http://www.loc.gov/MARC21/slim http://www.loc.gov/standards/marcxml/schema/MARC21slim.xsd");

$jelen = fgetcsv($in);
$keys = array();
foreach ($jelen as $key) {
    if (preg_match('/Type/', $key)) array_push($keys, "Type");
    else array_push($keys, trim($key));
}
$id = 1;
while ($line = fgetcsv($in)) {
    $result = array();

    $record = array_combine($keys, $line);

    // *********** BOOKS ***********
    if (($recordType == "book") && ($record["Type"] == "Book")) {
        $result["001"] = $id++;
        $result["005"] = date("YmdHis") . ".0";
        $result["007"] = "ta";
        if ($record["ISBN13"] != "") $result["020"]["a"] = $record["ISBN13"];
        if ($record["Author"] != "") {
            $authors = explode(";", $record["Author"]);
            $result["100"]["a"] = htmlspecialchars($authors[0]);
            $result["100"]["4"] = "aut";
            $ida = 0;
            foreach (array_slice($authors, 1) as $author) {
                $author = htmlspecialchars(trim($author));
                $author = str_replace(" ,", ", ", $author);
                $result["700"][$ida++]["a"] = $author;
            }
        }
        if ($record["Title"] != "") $result["245"]["a"] = htmlspecialchars($record["Title"]);
        if ($record["PublicationDate"] != "") {
            $result["260"]["c"] = htmlspecialchars($record["PublicationDate"]);
            $result["008"] = "------s" . htmlspecialchars($record["PublicationDate"]) . "------------s";
        } else $result["008"] = "------s----------------s";
        if ($record["URL"] != "") $result["856"]["u"] = htmlspecialchars($record["URL"]);

        $xmlWriter->startElement('record');
        $xmlWriter->startElement('leader');
        $xmlWriter->writeRaw("-----nam--22--------4500");
        $xmlWriter->endElement();

        $controlFields = [
            "001", "005", "007", "008"
        ];
        writeControlFields($result);
        /* r - repeated fields (700, 710)
           rs - repeated subfields (505)
        */
        $dataFields = [
            "020" => "020  ",
            "100" => "1001 ",
            "245" => "24500",
            "260" => "260  ",
            "700" => "7001 r",
            "856" => "85641",
        ];
        writeField($result);
        $xmlWriter->endElement(); // record
    }

    // *********** JOURNALS ***********
    if (($recordType == "journal") && ($record["Type"] == "Journal")) {
        $result["001"] = $id++;
        $result["005"] = date("YmdHis") . ".0";
        $result["007"] = "cr";
        if ($record["ISSN"] != "") $result["022"]["0"]["a"] = $record["ISSN"];
        if ($record["eISSN"] != "") $result["022"]["1"]["a"] = $record["eISSN"];
        if ($record["Author"] != "") {
            $authors = explode(";", $record["Author"]);
            $result["110"]["a"] = htmlspecialchars($authors[0]);
            $result["110"]["4"] = "aut";
            $ida = 0;
            foreach (array_slice($authors, 1) as $author) {
                $author = htmlspecialchars(trim($author));
                $author = str_replace(" ,", ", ", $author);
                $result["700"][$ida++]["a"] = $author;
            }
        }
        if ($record["Title"] != "") $result["245"]["a"] = htmlspecialchars($record["Title"]);
        $startDate = "----";
        if ($record["StartDate"] != "") {
            $startDate = parseDate($record["StartDate"]);

        }
        $endDate = "----";
        if ($record["EndDate"] != "") {
            $endDate = parseDate($record["EndDate"]);
        }
        $result["008"] = "-------c" . htmlspecialchars($startDate) . htmlspecialchars($endDate) . "------p-s-------------";
        if ($record["URL"] != "") $result["856"]["u"] = htmlspecialchars($record["URL"]);

        $xmlWriter->startElement('record');
        $xmlWriter->startElement('leader');
        $xmlWriter->writeRaw("-----nas--22-----2a-4500");
        $xmlWriter->endElement();

        $controlFields = [
            "001", "005", "007", "008"
        ];
        writeControlFields($result);
        /* r - repeated fields (700, 710)
           rs - repeated subfields (505)
        */
        $dataFields = [
            "022" => "022  r",
            "110" => "1101 ",
            "245" => "24500",
            "700" => "7001 r",
            "856" => "85641",
        ];
        writeField($result);
        $xmlWriter->endElement(); // record
    }
    // **********************

}
$xmlWriter->endElement(); // collection

?>
