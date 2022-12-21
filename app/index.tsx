import { render, h } from 'preact';

// import '../css/style.scss';

const App = () => (
	<div>
		<h1>Hello World</h1>
	</div>
);

const root = document.getElementById('root');
render(<App />, root);
