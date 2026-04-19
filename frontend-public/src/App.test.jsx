import {render, screen } from '@testing-library/react';
import App from './App';

test('paypal check', () => {
  render(<App />);
  screen.getByText(/Preis/i);
});
