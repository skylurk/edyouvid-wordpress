export const moduleIsActive = ( toolkitModule ) => {
	let isActive = false;

	if ( isDefined( window.ultpGutenbergModules ) ){
		isActive = ultpGutenbergModules.includes( toolkitModule );
	}
		
	return isActive;
}

export const isDefined = ( variable ) => {
    // Returns true if the variable is undefined
    return typeof variable !== 'undefined' && variable !== null;
}

export const isBoolean = val => 'boolean' === typeof val;