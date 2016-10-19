<?php
namespace RDM;

use DOMNode;
use DOMDocument;

class SvgFont
{

    private $id;

    private $unicodes;

    private $glyphs;

    public function __construct(DOMNode $fontElement)
    {
        $this->id = $fontElement->getAttribute('id');
        
        // Iterate over each glyph and store it in an array for fast access
        foreach ($fontElement->getElementsByTagName('glyph') as $glyphNode) {
            // Store each glyph id in an array using the glyph name as the key
            $this->unicodes[$glyphNode->getAttribute('glyph-name')] = $glyphNode->getAttribute('unicode');
            
            // Store data for each glyph in an array using the unicode value as the key
            $this->glyphs[$glyphNode->getAttribute('unicode')] = (object) [
                'path' => $glyphNode->getAttribute('d'),
                'advx' => $glyphNode->hasAttribute('horiz-adv-x') ? $glyphNode->getAttribute('horiz-adv-x') : $fontElement->getAttribute('horiz-adv-x'),
                'kern' => []
            ];
        }
        
        // Iterate over each kern element and add the data to the corresponding glyph
        foreach ($fontElement->getElementsByTagName('hkern') as $hkernNode) {
            // Convert u1 char list to array of unicode chars
            $kern1 = $hkernNode->hasAttribute('u1') ? explode(',', $hkernNode->getAttribute('u1')) : [];
            // Add mapped g1 glyph names list to array of unicode chars 
            if ($hkernNode->hasAttribute('g1')) {
                foreach (explode(',', $hkernNode->getAttribute('g1')) as $g1) {
                    $kern1[] = $this->unicodes[$g1];
                }
            }
            
            // Convert u2 char list to array of unicode chars
            $kern2 = $hkernNode->hasAttribute('u2') ? explode(',', $hkernNode->getAttribute('u2')) : [];
            // Add mapped g2 glyph names list to array of unicode chars
            if ($hkernNode->hasAttribute('g2')) {
                foreach (explode(',', $hkernNode->getAttribute('g2')) as $g2) {
                    $kern2[] = $this->unicodes[$g2];
                }
            }
            
            // Iterate over each char and add kern data to the glyph object
            foreach ($kern1 as $u1) {
                foreach ($kern2 as $u2) {
                    $this->glyphs[$u1]->kern[$u2] = $hkernNode->getAttribute('k');
                }
            }
        }
    }

    public function generateSvg($text, $options = [])
    {
        $chars = preg_split('//u', $text, null, PREG_SPLIT_NO_EMPTY);
        $document = new DOMDocument();
        $group = $document->createElement('g');
        $advance = 0;
        
        foreach ($chars as $i => $char) {
            $glyph = $this->glyphs[$char];
            
            // Create path element
            $path = $document->createElement('path');
            $path->setAttribute('d', $glyph->path);
            $path->setAttribute('fill-rule', 'evenodd');
            $path->setAttribute('transform', 'translate(' . $advance . ', 1000) scale(1, -1)');
            
            // Add glyph advance to total
            $advance += $glyph->advx + $options['letter-spacing'];
            
            // Subtract kern value from advance total if kerning is enabled
            if (($i + 1) < mb_strlen($text) && isset($glyph->kern[$chars[$i + 1]])) {
                $advance -= $glyph->kern[$chars[$i + 1]];
            }
            
            $group->appendChild($path);
        }
        
        return $group;
    }
}
