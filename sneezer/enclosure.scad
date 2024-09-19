$fn = 100;

boxWidth = 51;
boxLength = 22.7;

holeRadius = 1.45;
edgeMargin = 0.5;

difference() {
    union() {
        translate([0, 0, -1])
        cube([boxWidth * 1.5, boxLength * 1.5, 1.5], center = true);
   
        cube([boxWidth, boxLength, 1.5], center = true);
    }
    
    translate([-boxWidth/2 + holeRadius + edgeMargin, boxLength/2 - holeRadius - edgeMargin, 0])
    cylinder(20, r = holeRadius, center = true);
    
    translate([boxWidth/2 - holeRadius - edgeMargin, boxLength/2 - holeRadius - edgeMargin, 0])
    cylinder(20, r = holeRadius, center = true);
    
    translate([boxWidth/2 - holeRadius - edgeMargin, - boxLength/2 + holeRadius + edgeMargin, 0])
    cylinder(20, r = holeRadius, center = true);
    
    translate([-boxWidth/2 + holeRadius + edgeMargin, - boxLength/2 + holeRadius + edgeMargin, 0])
    cylinder(20, r = holeRadius, center = true);
    
    translate([boxWidth * 1.3 / 2, 0, 0])
    cylinder(20, r = holeRadius * 1.5, center = true);
    
    translate([-boxWidth * 1.3 / 2, 0, 0])
    cylinder(20, r = holeRadius * 1.5, center = true);
}
