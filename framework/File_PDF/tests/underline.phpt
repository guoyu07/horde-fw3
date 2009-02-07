--TEST--
File_PDF: Underline test
--FILE--
<?php

require_once dirname(__FILE__) . '/../PDF.php';

// Set up the pdf object.
$pdf = &File_PDF::factory(array('orientation' => 'P', 'format' => 'A4'));
// Start the document.
$pdf->open();
// Deactivate compression.
$pdf->setCompression(false);
// Start a page.
$pdf->addPage();
// Set font to Courier 8 pt.
$pdf->setFont('Helvetica', 'U', 12);
// Write underlined text.
$pdf->write(15, "Underlined\n");
// Write linked text.
$pdf->write(15, 'Horde', 'http://www.horde.org');
// Print the generated file.
echo $pdf->getOutput();

?>
--EXPECTF--
%PDF-1.3
3 0 obj
<</Type /Page
/Parent 1 0 R
/Resources 2 0 R
/Annots [<</Type /Annot /Subtype /Link /Rect [31.19 755.76 63.86 743.76] /Border [0 0 0] /A <</S /URI /URI (http://www.horde.org)>>>>]
/Contents 4 0 R>>
endobj
4 0 obj
<</Length 161>>
stream
2 J
0.57 w
BT /F1 12.00 Tf ET
BT 31.19 788.68 Td (Underlined) Tj ET 31.19 787.48 58.02 -0.60 re f
BT 31.19 746.16 Td (Horde) Tj ET 31.19 744.96 32.68 -0.60 re f

endstream
endobj
1 0 obj
<</Type /Pages
/Kids [3 0 R ]
/Count 1
/MediaBox [0 0 595.28 841.89]
>>
endobj
5 0 obj
<</Type /Font
/BaseFont /Helvetica
/Subtype /Type1
/Encoding /WinAnsiEncoding
>>
endobj
2 0 obj
<</ProcSet [/PDF /Text /ImageB /ImageC /ImageI]
/Font <<
/F1 5 0 R
>>
>>
endobj
6 0 obj
<<
/Producer (Horde PDF)
/CreationDate (D:%d)
>>
endobj
7 0 obj
<<
/Type /Catalog
/Pages 1 0 R
/OpenAction [3 0 R /FitH null]
/PageLayout /OneColumn
>>
endobj
xref
0 8
0000000000 65535 f 
0000000432 00000 n 
0000000615 00000 n 
0000000009 00000 n 
0000000222 00000 n 
0000000519 00000 n 
0000000703 00000 n 
0000000779 00000 n 
trailer
<<
/Size 8
/Root 7 0 R
/Info 6 0 R
>>
startxref
882
%%EOF
