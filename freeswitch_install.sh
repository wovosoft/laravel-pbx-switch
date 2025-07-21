
git clone
cd freeswitch
./bootstrap.sh -j
export FS_INSTALL_DIR="$PWD/_install"                                                                                                                                                                              (master|✚1-3
./configure --prefix="$FS_INSTALL_DIR"
make
make install
