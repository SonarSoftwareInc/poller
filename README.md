# Sonar Poller

## Adding new devices

Edit `config/devices.json` and add a response value which should be the response to an SNMP get to `1.3.6.1.2.1.1.2.0`. Add a device value which is a string representing the entire namespace and class name of the mapper in question.

If the class name is a mapper inside `src/DeviceMappers`, then the mapper must extend `BaseDeviceMapper`. If the class name is an identifier inside `src/DeviceIdentifiers`, then the identifier must implement `IdentifierInterface`. Almost all responses should be a mapper - the identifier path is only needed if the vendor doesn't uniquely identify their devices by a response to `1.3.6.1.2.1.1.2.0`.

Check out an existing mapper for examples of the best way to implement a new one.
