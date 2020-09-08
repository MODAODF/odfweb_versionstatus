// console.log('Load redicon JS')
var appName = 'ndcversionstatus'
debugger
if (sessionStorage.getItem('ndcversionstatus_lastCheck') < (Date.now() - 3600*1000)) {
    OCP.WhatsNew.query(); // for Nextcloud server
    sessionStorage.setItem('ndcversionstatus_lastCheck', Date.now());
}