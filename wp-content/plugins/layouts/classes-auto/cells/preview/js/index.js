/**
 * @since 2.5.2
 * For now a proof of concept for testers that React works in Layouts editor page without any conflict
 */
const { Component, render } = wp.element;

class App extends Component {
	render() {
		return (
			<div>
				<h2 style={ { color: '#f1f1f1' } }>React in Layouts!</h2>
			</div>
		);
	}
}

render( <App />, document.getElementById( 'cells-preview-root' ) );
