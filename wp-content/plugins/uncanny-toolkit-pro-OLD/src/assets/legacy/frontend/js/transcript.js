/**
 * Class representing printing functionality for a specific element with a custom stylesheet.
 */
class CustomStylePrinter {
	/** URL of the custom stylesheet to be used for printing. */
	static specificStyleSheets = ULTP_Transcript.print_version_styles;

	/** Elements to be added to the transcript. We'll add dynamic CSS using this approach. */
	static specificElements = ULTP_Transcript.print_version_elements;

	/**
	 * Creates a CustomStylePrinter.
	 * @param {String} elementId - The ID of the element to print.
	 */
	constructor( elementId ) {
		this.elementId = elementId;
	}

	/**
	 * Creates an invisible iframe for printing.
	 * @returns {HTMLIFrameElement} The created iframe.
	 */
	createIframeForPrinting() {
		const iframe = document.createElement( 'iframe' );
		iframe.style.position = 'absolute';
		iframe.style.width = '0';
		iframe.style.height = '0';
		iframe.style.border = 'none';
		document.body.appendChild( iframe );
		return iframe;
	}

	/**
	 * Prints the specified element with the custom style.
	 * 
	 * @param {Function} callback - The callback function to be executed after printing.
	 */
	print( callback = () => {} ) {
		const element = document.getElementById( this.elementId );

		if ( ! element ) {
			console.error( 'CustomStylePrinter: Element not found:', this.elementId );
			return;
		}

		const iframe = this.createIframeForPrinting();

		// Constructing HTML content for the iframe
		const content = `
			<html>
				<head>
					${ CustomStylePrinter.specificStyleSheets.map( stylesheet =>
						`<link rel="stylesheet" type="text/css" href="${ stylesheet }">`
					).join( '' ) }
					<title>${ document.title }</title>
				</head>
				<body>
					${ CustomStylePrinter.specificElements.map( elementSelector => {
						const element = document.querySelector( elementSelector );
						return element ? element.outerHTML : '';
					} ).join( '' ) }

					${ element.outerHTML }
				</body>
			</html>
		`;

		// Creating a Blob from the HTML content
		const blob = new Blob( [ content ], { type: 'text/html' } );
		// Setting the iframe source to the Blob URL
		iframe.src = URL.createObjectURL( blob );

		iframe.onload = () => {
			setTimeout( () => {
				iframe.contentWindow.focus();
				iframe.contentWindow.print();

				setTimeout( () => {
					document.body.removeChild( iframe );
					URL.revokeObjectURL( iframe.src ); // Cleanup: Release memory used by the Blob
				}, 1000 ); // Delay to ensure print dialog has been initiated

				callback();
			}, 1000 );
		};
	}
}

/**
 * Initializes the print functionality on the page.
 */
class PrintInitializer {
	/**
	 * Initializes the print button functionality.
	 */
	static initialize() {
		const printButton = document.getElementById( 'uo-ultp-transcript__print-trigger' );
		if ( ! printButton ) {
			console.error( 'PrintInitializer: Print button not found' );
			return;
		}

		const printer = new CustomStylePrinter( 'uo-ultp-transcript__document' );

		printButton.addEventListener( 'click', () => {
			// Add loading animation to the print button
			printButton.classList.add( 'uo-ultp-transcript__print-btn--loading' );

			// Print element
			printer.print( () => {
				// Remove loading animation
				printButton.classList.remove( 'uo-ultp-transcript__print-btn--loading' );
			} );
		} );
	}
}

// Run the script either after DOMContentLoaded or immediately if DOM has already loaded
if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', PrintInitializer.initialize );
} else {
	PrintInitializer.initialize();
}
