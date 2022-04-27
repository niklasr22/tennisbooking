import {render, screen } from '@testing-library/react';
import App from './App';

test('paypal check', () => {
  render(<App />);

  const linkElement = screen.getByText(/Preis/i);
  expect(linkElement).toBeInTheDocument();
});
