# Sonar Poller

![Sonar Poller](screenshot.png)

## Introduction

This poller provides a way to collect data from your network and return it to Sonar. This poller only works with Sonar v2 - please check out the [legacy poller](https://github.com/sonarsoftwareinc/poller-v1) if you're on Sonar version 1.

## Installation

Coming soon.

## Adding new types of devices

Edit `config/devices.json` and add a response value which should be the response to an SNMP get to `1.3.6.1.2.1.1.2.0`. Add a device value which is a string representing the entire namespace and class name of the mapper in question.

If the class name is a mapper inside `src/DeviceMappers`, then the mapper must extend `BaseDeviceMapper`. If the class name is an identifier inside `src/DeviceIdentifiers`, then the identifier must implement `IdentifierInterface`. Almost all responses should be a mapper - the identifier path is only needed if the vendor doesn't uniquely identify their devices by a response to `1.3.6.1.2.1.1.2.0`.

Check out an existing mapper for examples of the best way to implement a new one. The Netonix/Ws6Mini, Ubiquiti/Toughswitch or MikroTik/MikroTik mappers have examples of using non-SNMP based data collection as well.
