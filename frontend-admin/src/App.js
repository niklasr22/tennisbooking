import './App.css';

class Login extends React.Component {

  constructor(props) {
    super(props);
  }

  login() {

  }

  render() {
    return (
      <div>
        <form onSubmit={() => this.handleSubmit()}>
          <input type="text" placeholder="Nutzername" name="username"/>
          <input type="password" placeholder="Passwort" name="password"/>
          <button type="submit">Login</button>
        </form>
      </div>
    )
  }
}

function App() {
  return (
    <div className="App">
      
    </div>
  );
}

export default App;
