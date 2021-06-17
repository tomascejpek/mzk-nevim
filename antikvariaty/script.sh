#!/bin/sh

cd /home/tomas/data/antikvariat/

sed -i 's/\&amp;gt[.,]/\&amp;gt;/g' oai-all.xml
sed -i 's/\&amp;gt</\&amp;gt;</g' oai-all.xml
sed -i 's/\&amp;gt /\&amp;gt; /g' oai-all.xml

sed -i 's/\&amp;apos[.,]/\&amp;apos;/g' oai-all.xml
sed -i 's/\&amp;apos</\&amp;apos;</g' oai-all.xml
sed -i 's/\&amp;apos /\&amp;apos; /g' oai-all.xml

sed -i 's/\&amp;quot[.,]/\&amp;quot;/g' oai-all.xml
sed -i 's/\&amp;quot</\&amp;quot;</g' oai-all.xml
sed -i 's/\&amp;quot /\&amp;quot; /g' oai-all.xml
