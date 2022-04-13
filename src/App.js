import './App.css';
import React from 'react';
import logo from './logo.svg';
import {PAYPAL_CLIENT_ID} from './config.js'
import { PayPalScriptProvider, PayPalButtons } from "@paypal/react-paypal-js";

const initialOptions = {
  "client-id": PAYPAL_CLIENT_ID,
  currency: "EUR"
};

const endpoint = "http://tennisbooking.by-rousset.de/api.php/";

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

class OrderForm extends React.Component {

  constructor(props) {
    super(props)
    this.state = {
      price: 0, 
      counters: <Spinner />,
      plans: [],
      selection: [],
      duration: 1,
      paymentEnabled: false,
      info: "Es müssen mindestens 2 Spieler eingetragen werden."
    }
  }

  counterChange(plan, amount) {
    let selection = this.state.selection.slice();
    selection[plan] = amount;
    let newState = Object.assign({}, this.state, {selection: selection});
    this.setState(newState, () => this.updatePrice());
  }

  durationChange(duration) {
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

  componentDidMount() {
    fetch(endpoint + 'plans')
      .then(response => response.json())
      .then(data => {
        this.setState({
          price: this.state.price,
          plans: data.plans,
          counters: data.plans.map((item,index) => {
            return (<Counter label={item.name + " (" + priceString(item.price) + ")"} key={index} identfier={index} upperBounds="4" onCounterUpdated={(p, a) => this.counterChange(p, a)} />)
          }),
          selection: Array(data.plans.length).fill(0)
        });
      })
      .catch(error => {
        console.error('Error:', error);
      });
  }

  render() {
    return (
      <div className='OrderForm'>
        <p>Bitte geben Sie unten die Anzahl der Spieler je nach Status an. Es handelt sich insgesamt um eine Buchung für <strong>einen</strong> Tennisplatz.</p>
        <div className='PlayerTypes'>
          {this.state.counters}
        </div>
        <Counter label="Spieldauer in Stunden" upperBounds="8" lowerBounds="1" defaultValue="1" onCounterUpdated={(_p,v) => this.durationChange(v)}/>
        <div className='Pricing'><span>Preis:</span><span>{priceString(this.state.price)}</span></div>
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
                let orderData = {items: this.state.selection.map((item, index) => ({id: this.state.plans[index].id, quantity: item}))};
                return fetch(endpoint + "orders/create", {
                  method: "post",
                  headers: {
                    'Content-Type': 'application/json'
                  },
                  body: JSON.stringify(orderData)
                }).then((response) => response.json())
                  .then((order) => {
                    console.log(order);
                    return order.id;
                  });
              }}
              onApprove={(data, _a) => {
                console.log("finished payment");
                  return fetch(endpoint + `orders/${data.orderID}/capture`, {
                    method: "post",
                  })
                    .then((response) => {
                      let text = response.text();
                      console.log(text);
                      return JSON.parse(text);
                    })
                    .then((orderData) => {
                      console.log(orderData);
                    });
              }} />
        </PayPalScriptProvider>
      </div>
    );
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
