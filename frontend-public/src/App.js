import './App.css';
import React from 'react';
import logo from './logo.svg';
import {PAYPAL_CLIENT_ID} from './config.js'
import { PayPalScriptProvider, PayPalButtons } from "@paypal/react-paypal-js";

const initialOptions = {
  "client-id": PAYPAL_CLIENT_ID,
  currency: "EUR"
};

const endpoint = "https://tennisbooking.by-rousset.de/api.php/";

function priceString(price) {
  return Number(price).toLocaleString("de", {minimumFractionDigits: 2, maximumFractionDigits: 2}) + "€";
}

class Counter extends React.Component {

  constructor(props) {
    super(props);
    this.state = { counterValue: parseInt(this.props.defaultValue) };
  }

  increase() {
    let upperBounds = parseInt(this.props.upperBounds);
    this.setState(
      state => ({
        counterValue: state.counterValue < upperBounds ? state.counterValue + 1 : upperBounds
      }),
      () => {
        if (this.props.onCounterUpdated !== undefined)
          this.props.onCounterUpdated(this.props.identfier, this.state.counterValue);
      });
  }

  decrease() {
    let lowerBounds = parseInt(this.props.lowerBounds);
    this.setState(
      state => ({
        counterValue: state.counterValue > lowerBounds ? state.counterValue - 1 : lowerBounds
      }),
      () => {
        if (this.props.onCounterUpdated !== undefined)
          this.props.onCounterUpdated(this.props.identfier, this.state.counterValue);
      });
  }

  render() {
    return (
      <div className="Counter">
        <span className="CounterLabel">{this.props.label}</span>
        <div>
          <button className='CounterDecrease' onClick={() => this.decrease()}>-</button>
          <span className='CounterValue'>{this.state.counterValue}</span>
          <button className='CounterIncrease' onClick={() => this.increase()}>+</button>
        </div>
      </div>)
  }
}

Counter.defaultProps = {
  upperBounds: Infinity,
  lowerBounds: 0,
  defaultValue: 0
};

function Spinner() {
  return (<div className='Spinner'></div>)
}

let orderFormDefault = {
  price: 0, 
  counters: <Spinner />,
  plans: [],
  selection: [],
  duration: 1,
  paymentEnabled: false,
  info: "Es müssen mindestens 2 Spieler eingetragen werden.",
  completed: false,
  order: null
};

class OrderForm extends React.Component {

  constructor(props) {
    super(props)
    this.state = Object.assign({}, orderFormDefault);
  }

  counterChange(plan, amount) {
    let selection = this.state.selection.slice();
    selection[plan] = amount;
    let newState = Object.assign({}, this.state, {selection: selection});
    this.setState(newState, () => this.updatePrice());
  }

  durationChange(duration) {
    console.log("change");
    let newState = Object.assign({}, this.state, {duration: duration});
    this.setState(newState, () => this.updatePrice());
  }

  updatePrice() {
    let prices = [];
    this.state.selection.forEach((val, index) => prices.push(...Array(val).fill(this.state.plans[index].price)));
    prices = prices.sort();
    let price = 0;
    let paymentEnabled = false
    let info = "Es müssen mindestens 2 Spieler eingetragen werden."
    if (prices.length >= 2) {
      price = (prices[0] + prices[1]) * this.state.duration;
      if (price > 0.0) {
        paymentEnabled = true;
        info = "";
      } else {
        info = "Das Spielen ist für alle ausgewählten Spieler kostenfrei.";
      }
    }
    let newState = Object.assign({}, this.state, {price: price, paymentEnabled: paymentEnabled, info: info});
    this.setState(newState);
  }

  updatePlans() {
    fetch(endpoint + 'plans')
      .then(response => response.json())
      .then(data => {
        this.setState(Object.assign({}, this.state, {
          plans: data.plans,
          counters: data.plans.map((item,index) => {
            return (<Counter label={item.name + " (" + priceString(item.price) + ")"} key={index} identfier={index} upperBounds="4" onCounterUpdated={(p, a) => this.counterChange(p, a)} />)
          }),
          selection: Array(data.plans.length).fill(0)
        }));
      })
      .catch(error => {
        console.error('Error:', error);
      });
  }

  componentDidMount() {
    this.updatePlans();
  }

  showError(msg) {
    this.setState(Object.assign({}, this.state, {info: <span className='Error'>{msg}</span>}));
  }

  setOrderCompleted(completed, order=null) {
    if (!completed)
      this.setState(Object.assign({}, orderFormDefault), () => this.updatePlans());
    else
      this.setState(Object.assign({}, this.state, {completed: completed, order: order}));
  }

  showOrderCreation() {
    return (
      <div className='OrderForm'>
        <p>Bitte geben Sie unten die Anzahl der Spieler je nach Status an. Es handelt sich insgesamt um eine Buchung für <strong>einen</strong> Tennisplatz.</p>
        <div className='PlayerTypes'>
          {this.state.counters}
        </div>
        <Counter label="Spieldauer in Stunden" upperBounds="8" lowerBounds="1" defaultValue="1" onCounterUpdated={(_p,v) => this.durationChange(v)}/>
        <div className='Pricing'><span>$$$ Preis:</span><span>{priceString(this.state.price)}</span></div>
        <p>{this.state.info}</p>
        <PayPalScriptProvider options={initialOptions}>
            <PayPalButtons 
              className="Payment" 
              fundingSource="paypal" 
              style={{
                shape: 'pill',
                color: 'silver',
                layout: 'horizontal',
                label: 'pay'}}
              disabled={!this.state.paymentEnabled}
              createOrder={(_d, _a) => {
                let orderData = {items: this.state.selection.map((item, index) => ({id: this.state.plans[index].id, quantity: item})), duration: this.state.duration};
                return fetch(endpoint + "orders/create", {
                  method: "post",
                  headers: {
                    'Content-Type': 'application/json'
                  },
                  body: JSON.stringify(orderData)
                }).then((response) => response.json())
                  .then((data) => {
                    if (data.state === "paymentInitiated") {
                      return data.id;
                    } else if (data.state === "noOrderRequired") {
                      this.showError("Das Spielen ist für alle ausgewählten Spieler kostenfrei.");
                    } else {
                      this.showError("Leider konnte Ihre Anfrage nicht fehlerfrei bearbeitet werden. Bitte wenden Sie sich an den Tennisverein.");
                    }
                    return -1;
                  });
              }}
              onApprove={(data, _a) => {
                console.log("finished payment");
                  return fetch(endpoint + `orders/${data.orderID}/capture`, {
                    method: "post",
                  }).then((response) => response.json())
                    .then((data) => {
                      if (data.state === "success") {
                        console.log("okcool")
                        this.setOrderCompleted(true, data.order)
                      } else {
                        this.showError("Leider konnte Ihre Anfrage nicht fehlerfrei bearbeitet werden. Bitte wenden Sie sich mit Ihrer PayPal-Transaktionsnummer an den Tennisverein.");
                      }
                    });
              }} />
        </PayPalScriptProvider>
      </div>
    )
  }

  showOrderResumee() {
    return (
      <div className='OrderForm'>
        <h2>Buchung erfolgreich</h2>
        <p className='OrderCode'>Code: {this.state.order.code}</p>
        <p><strong>Bitte speichern Sie Ihren Code unbedingt ab!</strong></p>
        <div className='OrderDetails'>
          <p>Buchung für folgende Spieler:</p>
          <ul>
            {this.state.order.items.items.map((item, _i) => {
              return (<li>{item.quantity}x {item.name}</li>)
            })}
          </ul>
          <p>davon bezahlt:</p>
          <ul>
            {this.state.order.items.payedItems.map((item, _i) => {
              return (<li>{item.quantity}x {item.name}: {priceString(item.price * item.quantity)}</li>)
            })}
          </ul>
          <p>Bestellungsnummer: {this.state.order.orderId}</p>
          <p>PayPal Transaktionsnummer: {this.state.order.paymentId}</p>
        </div>
        <h3>Viel Spaß!</h3>
        <button className='NewBooking' onClick={() => this.setOrderCompleted(false, null)}>Weitere Buchung</button>
      </div>
    )
  }

  render() {
    return (this.state.completed ? this.showOrderResumee() : this.showOrderCreation());
  }
}

function App() {
  return (
    <div className="App">
      <div className='LogoWrapper'><img src={logo} alt="Logo"/></div>
      <h1>Tennisplatz-Buchung</h1>
      <OrderForm />
    </div>
  );
}

export default App;
